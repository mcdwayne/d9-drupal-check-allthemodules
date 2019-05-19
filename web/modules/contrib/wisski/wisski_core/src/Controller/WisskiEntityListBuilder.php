<?php

namespace Drupal\wisski_core\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

use Drupal\image\Entity\ImageStyle;

use Drupal\wisski_core\WisskiCacheHelper;
use Drupal\wisski_core\WisskiStorage;

/**
 * Provides a list controller for wisski_core entity.
 *
 */
class WisskiEntityListBuilder extends EntityListBuilder {

  private $bundle;
  
  private $num_entities;
  private $image_height;
  private $page;
  private $there_is_more = FALSE;
  
  private $adapter;
  private $preview_image_adapters = array();
  
  /**
   * {@inheritdoc}
   * We show our entity list either as a grid of a given width e.g. three entities wide
   * or as a single-entity-wide table with additional action buttons, which provide
   * e.g. direct edit or delete actions.
   * We override ::render() so that we can change the view type of the list
   * we avoid ::buildHeader() since we do not necessarily have one.
   * We also do not use buildRow() but instead introduce buildRowForId() to be able to load info without
   * having to load all the entities
   */
  public function render($bundle = '',$entity=NULL) {
#    dpm("1: " . microtime());
    //if (!isset($this->limit))
    $this->limit = \Drupal::config('wisski_core.settings')->get('wisski_max_entities_per_page');
    $this->bundle = \Drupal::entityManager()->getStorage('wisski_bundle')->load($bundle);

    $build['#title'] = isset($this->bundle) ? $this->bundle->label() : $this->t('WissKI Entities');
    
    // setting $this->adapter may be obsolete now that we have preview_image_adapters
    $pref_local = \Drupal\wisski_salz\AdapterHelper::getPreferredLocalStore();
    if (!$pref_local) {
      $build['error'] = array(
        '#type' => 'markup',
        '#markup' => $this->t('There is no preferred local store'),
      );
    } else {
      $this->adapter = $pref_local;
    
      $this->preview_image_adapters = \Drupal::config('wisski_core.settings')->get('preview_image_adapters');
      if (empty($this->preview_image_adapters)) {
        $this->preview_image_adapters = array($pref_local);
      }
    }
    
    //gather the page attributes from the request, this resembles a REST query
    $request_query = \Drupal::request()->query;
    //dpm($request_query,'HTTP GET');
    
    $columns = \Drupal::config('wisski_core.settings')->get('wisski_default_columns_per_page');
    $grid_type = $request_query->get('type') ? : 'grid';
    $grid_width = $request_query->get('width') ? : !empty($columns) ? $columns : 3;
    $this->page = $request_query->get('page') ? : 0;
    //dpm($grid_type.' '.$grid_width);
#    dpm("2: " . microtime());
    //if we have a real table, we need a header
    if ($grid_type === 'table') {
      $header = array('preview_image'=>$this->t('Entity'),'title'=>'','operations'=>$this->t('Operations'));
    }
    if ($grid_type === 'grid') {
      $header = NULL;
    }
    
    //the 'table' element will be used in both types
    $build['table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#title' => $this->getTitle(),
      '#rows' => array(),
      '#empty' => $this->t('There is no @label yet.', array('@label' => $this->entityType->getLabel())),
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => $this->entityType->getListCacheTags(),
      ],
      '#prefix' => '<div id="wisski-entity-list">',
      '#suffix' => '</div>',
    );
    
    //collect the entities to show in this list and...
    $entities = $this->getEntityIds();
    //dpm($entities,'Entities to load');
    
    //...let the CacheHelper prepare for the preview image request
    //this speeds up things a little
    WisskiCacheHelper::preparePreviewImages($entities);
    $this->storage->preparePreviewImages();
#    dpm("3: " . microtime());
    if ($grid_type === 'table') {
      //now, if we have a table
      foreach ($entities as $entity_id) {
        
        if ($input_row = $this->buildRowForId($entity_id)) {
          $build['table']['#rows'][$entity_id] = array(
            'preview_image' => array(
              //we want the preview image to be a correct link to the entity
              'data' => array(
                '#markup' => isset($input_row['preview_image']) 
                  ? '<a href='.$input_row['url']->toString().'>'.$input_row['preview_image'].'</a>'
                  : '<a href='.$input_row['url']->toString().'>'.$this->t('No preview available').'</a>'
                  ,
              ),
            ),
            'title' => array('data' => array(
              //the title shall also be a link
              '#type' => 'link',
              '#title' => $input_row['label'],
              '#url' => $input_row['url'],
            )),
            'operations' => array(
              //the operations multibutton normally containing view, edit, and delete
              'data' => array(
                '#type' => 'operations',
                '#links' => $input_row['operations'],
              ),
            ),
          );
        }
      }
    }
#    dpm("4: " . microtime());
    if ($grid_type === 'grid') {
      //in case we have the grid view, we need some additional computation
      //so we keep the row number and the number of the cel in the row
      $row_num = 0;
      $cell_num = 0;
      //in every "round" we fill the $row array with single render-element-cells for each entity
      $row = array();
#      dpm($ents,'list');
      
      foreach ($entities as $entity_id) {
        if ($input_cell = $this->buildRowForId($entity_id)) {
          //each shown cell shall consist of the preview image and the entity title
          //each as a link to the entity page
          $cell_data = array(
            '#type' => 'container',
          );
          if (isset($input_cell['preview_image'])) {
            $cell_data['preview_image'] = array(
              '#type' => 'link',
              '#title' => $input_cell['preview_image'],
              '#url' => $input_cell['url'],
              '#suffix' => '<br/>',
            );
          }
          $cell_data['title'] = array(
            '#type' => 'link',
            '#title' => $input_cell['label'],
            '#url' => $input_cell['url'],
          );
          $row[$cell_num] = array('data' => $cell_data);
          //prepare for the next entity in the same row
          $cell_num++;
          if ($cell_num == $grid_width) {
            //if the row is full, "print" the row
            $build['table']['#rows']['row'.$row_num] = $row;
            //and then we have to proceed with the first [0] entity
            //in the nxt row
            $row_num++;
            $row = array();
            $cell_num = 0;
          }
        }  
      }  
      //add the last row that might not have been filled to the full extent
      if ($cell_num > 0) $build['table']['#rows']['row'.$row_num] = $row;
    }
#    dpm("5: " . microtime());
    /*
    $build['custom_pager']['#type'] = 'container';
    $build['custom_pager']['#attributes']['class'] = 'text-align-center';
    
    $build['custom_pager']['first_link'] = array(
      '#type' => 'link',
      '#title' => '<<'.$this->t('First'),
      '#url' => Url::fromRoute('<current>',array('page'=>0)),
      '#suffix' => '&nbsp;&nbsp;',
    );
    
    if ($this->page <= 1) {
      $build['custom_pager']['first_link']['#attributes']['class'] = 'invisible';
    }

    $build['custom_pager']['back_link'] = array(
      '#type' => 'link',
      '#title' => '<'.$this->t('Previous'),
      '#url' => Url::fromRoute('<current>',array('page'=>$this->page-1)),
      '#suffix' => '&nbsp;&nbsp;',
    );
    
    if ($this->page < 1) {
      $build['custom_pager']['back_link']['#attributes']['class'] = 'invisible';
    }
    
    $build['custom_pager']['next_link'] = array(
      '#type' => 'link',
      '#title' => $this->t('Next').'>',
      '#url' => Url::fromRoute('<current>',array('page'=>$this->page+1)),
    );
    
    if ($this->there_is_more === FALSE) {
      $build['custom_pager']['next_link']['#attributes']['class'] = 'invisible';
    }
    */
    
    //this adds a box for the selection of the grid size
    $build['grid_type'] = $this->getGridTypeBlock();
    
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $build['pager'] = array(
        '#type' => 'pager',
      );
    }
    
//    dpm($build);
    return $build;
  }

  /**
   * {@inheritdoc}
   * We only load entities from the specified bundle
   */
  protected function getEntityIds() {
    //dpm($this,__METHOD__); 
  
    //get us a WisskiQueryDelegator object and give it a sort key
    $storage = $this->getStorage();
    $query = $storage->getQuery();
    
#    $query->sort($this->entityType->getKey('id'));

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
      $query->range($this->page*$this->limit,$this->limit);
    }
    //dpm($query);
    if (!empty($this->bundle)) {
      if ($pattern = $this->bundle->getTitlePattern()) {
        //add the title parts to the query, non-optional parts must not be empty
        foreach ($pattern as $key => $attributes) {
          if ($attributes['type'] === 'path' && !$attributes['optional']) {
            $query->condition($attributes['name']);
          }
        }
      }
      //add the bunlde condition
      $query->condition('bundle',$this->bundle->id());

      //execute the query
#wisski_tick();
      $entity_ids = $query->execute();
#wisski_tick('query');

      foreach ($entity_ids as $eid) {
        //we expect the user to load one of the entites in the near future
        //so we cache this bundle here as the calling bundle for the entity
        $storage->writeToCache($eid,$this->bundle->id());
      }
      
      $return = $entity_ids;
    } else $return = $query->execute();
    $this->num_entities = count($return);
    /*
    if ($this->limit && $this->limit < $this->num_entities) {
      $this->there_is_more = TRUE;
      $this->num_entities--;
      array_pop($return);
    }
    */
    return $return;
  }
  
  /**
   * externally prepare the Listbuilder
   * this is necessary e.g. for views
   * @TODO: We just do this here because the list-builder
   * handles image-shit he shouldn't handle. This should
   * be improved.
   * @return returns true on sucess, false else.
   */
/*
  public function prepareWisskiEntityListBuilder() {
    $pref_local = \Drupal\wisski_salz\AdapterHelper::getPreferredLocalStore();
    if (!$pref_local) {
      return FALSE;
    } else {
      $this->adapter = $pref_local;
    
      $this->preview_image_adapters = \Drupal::config('wisski_core.settings')->get('preview_image_adapters');
      if (empty($this->preview_image_adapters)) {
        $this->preview_image_adapters = array($pref_local);
      }
    }
    return TRUE;
  }
*/
  private function getOperationLinks($entity_id) {
  
    //we have these hard-coded since there seems to be no possibility to generate fully qualified Route-URLs from
    //link templates without having the entity itself at hand, which we want to avoid here
    //add routes here to enhance the OPs list
    $operations = array(
      'view' => array('entity.wisski_individual.canonical',$this->t('View')),
      'edit' => array('entity.wisski_individual.edit_form',$this->t('Edit')),
      'delete' => array('entity.wisski_individual.delete_form',$this->t('Delete')),
    );
    $i = 0;
    $links = array();
    foreach ($operations as $key => list($route,$label)) {
      $links[$key] = array(
        'url' => Url::fromRoute($route,array('wisski_individual' => $entity_id,'wisski_bundle' => $this->bundle->id())),
        'weight' => $i++,
        'title' => $label,
      );
    }
    return $links;
  }
  
  /**
   * this provides a render element containing links that re-style the
   * list view, so either a table or a grid of given width will be shown.
   * The links aim to the <current> route i.e. the page already shown and add
   * query parameters
   */
  private function getGridTypeBlock() {
    
    $block = array(
      '#title' => $this->t('Show as ...'),
      '#type' => 'details',
      '#open' => FALSE,
    );
    $block['table'] = array(
      '#type' => 'fieldset',
      'link' => array(
        '#type' => 'link',
        '#url' => Url::fromRoute('<current>',array('type'=>'table')),
        '#title' => $this->t('Table with operation links'),
      ),
      '#title' => $this->t('Table'),
    );
    $block['grid'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Grid of width'),
    );
    foreach (range(2,10) as $width) {
      $ops[] = array(
        'url' => Url::fromRoute('<current>',array('type'=>'grid','width'=>$width)),
        'title' => $width,
      );
    }
    $block['grid']['links'] = array(
      '#type' => 'operations',
      '#links' => $ops,
    );
    return $block;
  }
  
  /**
   * re-written buildRow since we don't need to load the entity just to make its title
   */
  public function buildRowForId($entity_id) {
static $timeall = 0;
$timethis = microtime(TRUE);
    #dpm($this);
    #dpm($entity);
    //    dpm($entity->tellMe('id','bundle'));
    //    echo "Hello ".$id;
    //dpm($entity);
    //dpm($entity->get('preview_image'));
#    dpm("4.1: " . microtime());
    //let the bundle generate the entity title (normally from the title pattern)
    $entity_label = $this->bundle->generateEntityTitle($entity_id);
#    dpm("4.2: " . microtime());
    //create a link to the entity's "canonical" route, link templates
    //do not work here, again
    $entity_url = Url::fromRoute('entity.wisski_individual.canonical',array('wisski_bundle'=>$this->bundle->id(),'wisski_individual'=>$entity_id));

    $row = array(
      'label' => $entity_label,
      'url' => $entity_url,
    );
#    dpm("4.-: " . microtime());    
    //get the preview image URI and...
    $prev_uri = $this->storage->getPreviewImageUri($entity_id,$this->bundle->id());
#    dpm($prev_uri);
#    dpm("4.3: " . microtime());
    if ($prev_uri) {
      //...render the image
      $array = array(
        '#theme' => 'image',
        '#uri' => $prev_uri,
        '#alt' => 'preview '.$entity_label,
        '#title' => $entity_label,
      );
      //really, render it, so we have HTML-Markup that can be put between <a href=...></a> tags
      \Drupal::service('renderer')->renderPlain($array);
      $row['preview_image'] = $array['#markup'];
    }
#    dpm("4.4: " . microtime());
    //add the OP-links to the element
    $row['operations'] = $this->getOperationLinks($entity_id);
$timethis = microtime(TRUE) - $timethis;
$timeall += $timethis;

    return $row;
  } 
  
  /**
   * this gathers the URI i.e. some public:// or remote path to this entity's
   * preview image
   */
/*  public function getPreviewImageUri($entity_id,$bundle_id) {
#    dpm("4.2.1: " . microtime());
    
    //first try the cache
    $preview = WisskiCacheHelper::getPreviewImageUri($entity_id);
#    dpm("4.2.2: " . microtime());
#    dpm($preview,__FUNCTION__.' '.$entity_id);
    
    if ($preview) {
      //do not log anything here, it is a performance sink
      //\Drupal::logger('wisski_preview_image')->debug('From Cache '.$preview);
      if ($preview === 'none') return NULL;
      return $preview;
    }
#    dpm("4.2.3: " . microtime());
    //if the cache had nothing try the adapters
    //for this purpose we need the entity URIs, which are stored in the local
    //store, so if there is none, stop here
    if (empty($this->preview_image_adapters)) return NULL;

    // we iterate through all the selected adapters but we stop at the first
    // image that was successfully converted to preview image style as we only
    // need one!
    foreach ($this->preview_image_adapters as $adapter_id => $adapter) {
      
      if ($adapter === NULL || !is_object($adapter)) {
        // we lazy-load adapters
        $adapter = entity_load('wisski_salz_adapter', $adapter_id);
        if (empty($adapter)) {
          unset($this->preview_image_adapters[$adapter_id]);
          continue;
        } else {
          $this->preview_image_adapters[$adapter_id] = $adapter;
        }
      }

      if (empty(\Drupal\wisski_salz\AdapterHelper::getUrisForDrupalId($entity_id,$adapter->id()))) {
        if (WISSKI_DEVEL) \Drupal::logger('wisski_preview_image')->debug($adapter->id().' does not know the entity '.$entity_id);
        WisskiCacheHelper::putPreviewImageUri($entity_id,'none');
        return NULL;
      }

      //ask the local adapter for any image for this entity
      $images = $adapter->getEngine()->getImagesForEntityId($entity_id,$bundle_id);
#    dpm("4.2.4: " . microtime());

      if (empty($images)) {
        if (WISSKI_DEVEL) \Drupal::logger('wisski_preview_image')->debug('No preview images available from adapter '.$adapter->id());
        continue;
      }

      if (WISSKI_DEVEL) \Drupal::logger('wisski_preview_image')->debug("Images from adapter $adapter_id: ".serialize($images));
      //if there is at least one, take the first of them
      //@TODO, possibly we can try something mor sophisticated to find THE preview image
      $input_uri = current($images);
#    dpm("4.2.4.1: " . microtime());
      //now we have to ensure there is the correct image file on our server
      //and we get a derivate in preview size and we have this derivates URI
      //as the desired output
      $output_uri = '';
      
      //get a correct image uri in $output_uri, by saving a file there
      #$this->storage->getFileId($input_uri,$output_uri);
      // generalized this line for external use
      \Drupal::entityTypeManager()->getStorage('wisski_individual')->getFileId($input_uri, $output_uri);
#    dpm("4.2.4.2: " . microtime());
      //try to get the WissKI preview image style
      $image_style = $this->getPreviewStyle();
#    dpm("4.2.5: " . microtime());    
      //process the image with the style
      $preview_uri = $image_style->buildUri($output_uri);
      //dpm(array('output_uri'=>$output_uri,'preview_uri'=>$preview_uri));
      if ($out = $image_style->createDerivative($output_uri,$preview_uri)) {
        //drupal_set_message('Style did it - uri is ' . $preview_uri);
        WisskiCacheHelper::putPreviewImageUri($entity_id,$preview_uri);
        //we got the image resized and can output the derivates URI
        return $preview_uri;
      } else {
        drupal_set_message("Could not create a preview image for $input_uri. Probably its MIME-Type is wrong or the type is not allowed by your Imge Toolkit","error");
        WisskiCacheHelper::putPreviewImageUri($entity_id,NULL);
      }

    }

    return NULL;

  }*/
  
  //cache the style in this object in case it will be used for multiple entites
  #private $image_style;
  
  /**
   * loads and - if necessary - in advance generates the 'wisski_preview' ImageStyle
   * object
   * the style resizes - mostly downsizes - the image and converts it to JPG
   */
   /*
  private function getPreviewStyle() {

    //cached?    
    if (isset($this->image_style)) return $this->image_style;
    
    //if not, try to load 'wisski_preview'
    $image_style_name = 'wisski_preview';

    $image_style = ImageStyle::load($image_style_name);
    if (is_null($image_style)) {
      //if it's not there we generate one
      
      //first create the container object with correct name and label
      $values = array('name'=>$image_style_name,'label'=>'Wisski Preview Image Style');
      $image_style = ImageStyle::create($values);
      
      //then gather and set the default values, those might have been set by 
      //the user
      //@TODO tell the user that changing the settings after the style has
      //been created will not result in newly resized images
      $settings = \Drupal::config('wisski_core.settings');
      $w = $settings->get('wisski_preview_image_max_width_pixel');
      $h = $settings->get('wisski_preview_image_max_height_pixel');      
      $config = array(
        'id' => 'image_scale',
        'data' => array(
          //set width and height and disallow upscale
          //we believe 100px to be an ordinary preview size
          'width' => isset($w) ? $w : 100,
          'height' => isset($h) ? $h : 100,
          'upscale' => FALSE,
        ),
      );
wpm($config,'image style config');
      //add the resize effect to the style
      $image_style->addImageEffect($config);
      
      //configure and add the JPG conversion
      $config = array(
        'id' => 'image_convert',
        'data' => array(
          'extension' => 'jpeg',
        ),
      );
      $image_style->addImageEffect($config);
      $image_style->save();
    }
    $this->image_style = $image_style;
    return $image_style;
  }
  */

}
