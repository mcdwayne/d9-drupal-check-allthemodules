<?php

namespace Drupal\wisski_core\Plugin\views\query;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

use Drupal\wisski_salz\AdapterHelper;
use Drupal\wisski_core\WisskiCacheHelper;
use Drupal\wisski_core\Controller\WisskiEntityListBuilder;
use Drupal\wisski_adapter_sparql11_pb\Plugin\wisski_salz\Engine\Sparql11EngineWithPB;

/**
 * Views query plugin for an SQL query.
 *
 * @ingroup views_query_plugins
 *
 * @ViewsQuery(
 *   id = "wisski_individual_query",
 *   title = @Translation("WissKI Entity Query"),
 *   help = @Translation("Use WissKI Entities in Views backed by Drupal database API.")
 * )
 */
class WisskiIndividualQuery extends QueryPluginBase {

  /**
   * The EntityQuery object used for the query.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface, \Drupal\wisski_salz\Query\WissKIQueryDelegator in our case
   */
  public $query;
  
  /**
   * The fields that should be returned explicitly by the query in the
   * ResultRow objects
   * 
   * @var array, keys and values are the field IDs
   */
  public $fields = [];

  /**
   * The order statements for the query
   * 
   * @var array
   */
  public $orderby;
  
  /**
   * The variable counter for parameters
   */
  private $paramcount = 0;
  
  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
#    dpm(microtime(), "init");
    parent::init($view, $display, $options);
    $this->query = \Drupal::entityTypeManager()->getStorage('wisski_individual')->getQuery();
    $this->pager = $view->pager;  // TODO: do we need to set it here if pager is only inited in this->build()?
  }


  /**
   * Builds the necessary info to execute the query.
   */
  function build(ViewExecutable $view) {
#    dpm(microtime(), "build in!");
    $view->initPager();

    // Let the pager modify the query to add limits.
    $this->pager = $view->pager;
    if ($this->pager) {
      $this->pager->query();
    }
    $count_query = clone $this->query;
    $count_query->count(true);

    $view->build_info['wisski_query'] = $this->query;
    $view->build_info['wisski_count_query'] = $count_query;
#    dpm(microtime(), "build out!");
  }


  /**
   * We override this function as the standard field plugins use it.
   *
   * @param base_table not used
   * @param base_field the WisskiEntity entity query field
   *
   */
  function addField($base_table, $base_field) {
    $this->fields[$base_field] = $base_field;
    if (strpos($base_field, "wisski_path_") === 0) {
      // we always load the whole entity if the field is a path.
      // TODO: this is very slow when retrieving many entities; find a way to
      // get the field values without loading the entity.
      $this->fields['_entity'] = '_entity';
    }
    return $base_field;
  }
  
  
  /**
   * We override this function as the standard sort plugins use it
   *
   * @param table not used
   * @param field the WisskiEntity entity query field by which to sort
   * @param order sort order
   * @param alias not used
   * @param params not used
   */
  public function addOrderBy($table, $field = NULL, $order = 'ASC', $alias = '', $params = array()) {
    // $table is useless here
    if ($field) {
      $as = $this->addField($table, $field, $alias, $params);

      $this->orderby[] = array(
        'field' => $as, 
        'direction' => strtoupper($order),
      );
    }
    if ($table == "rand") {
      $this->orderby[] = array(
        'field' => $table,
        'direction' => strtoupper($order),
      );
    }
  }
  
  /**
   * We override this function as the standard sort plugins use it
   *
   * @param group ??
   * @param field the WisskiEntity entity query field by which to sort
   * @param value ??
   * @param operator ??
   */ 
  public function addWhere($group, $field, $value = NULL, $operator = NULL) {
#     drupal_set_message("AddWhere was called with " . serialize($group) . " and " . serialize($field) . " and " . $value . " and " . $operator, "warning");
//    $this->condition('id', 'wisski_individual.' . $bundleid . '.', 'STARTS_WITH');  
     // do nothing - this is already gathered below due $query is modified for 
     // every filter in the view.
  }
  
  /**
   * We override this function as the standard sort plugins use it
   * 
   * @param ????
   */
  public function addWhereExpression($group, $snippet, $args = array()) {
    // we dont have to act here, because there is all information in the substitutions below!
#    dpm("addWhere called with $group, $snippet, " . serialize($args), "error");
/*
    foreach($args as $placeholder => $arg) {
      if(strpos($placeholder, "wisski.placeholder") !== FALSE) {
        $this->condition(
      }
    }
    */
  }

  /** This function is called by Drupal\views\Plugin\views\HandlerBase
  * maybe we should eventually break up the inheritance from there/QueryPluginBase if possible.
  */
  public function ensureTable($t, $r) {
    // do nothing
  }
  
  public function query($get_count = FALSE) {
    
    $query = clone $this->query;

    // Add the query tags.
    if (!empty($this->options['query_tags'])) {
      foreach ($this->options['query_tags'] as $tag) {
        $query->addTag($tag);
      }
    }
    
    if ($get_count) {
      $query->count(); 
      return $query;
    }

    
    if($this->orderby) {
      foreach($this->orderby as $elem) {
        $query->sort($elem['field'], $elem['direction']);
      }
    }

    return $query;

  }


  /**
   * Executes the query and fills the associated view object with according
   * values.
   *
   * Values to set: $view->result, $view->total_rows, $view->execute_time,
   * $view->pager['current_page'].
   */
  function execute(ViewExecutable $view) {
#    dpm("yo");
#    return;
#  dpm($this->orderby, "orderby!");
#    dpm($view->field);
#dpm(microtime(), "begin execute");
#wisski_tick();
#wisski_tick("begin exec views");
    $query = $view->build_info['wisski_query'];
    $count_query = $view->build_info['wisski_count_query'];
    $args = $view->build_info['query_args'];

    $filter_regex = array();

    $bundle_ids = array();
    $entity_id = NULL;
#    dpm($view->filter, "filt");
    if(!empty($view->filter)) {
      foreach($view->filter as $key => $one_filter) {
        if($key == "bundle") {
#          dpm(serialize($one_filter), "onefilter!!");
          $bundle_ids = array_merge($bundle_ids, $one_filter->value);
        } else {

#          dpm(serialize($one_filter), "one filter");        
          // special case - omit filter for empty values.
          if($one_filter->value == "" && $one_filter->operator != 'is_empty') {
            continue;
          }
#          dpm(serialize($one_filter));
#          dpm($one_filter->value, "value");
#          $filter_regex[$key][] = array('op' => $one_filter->operator, 'val' => $one_filter->value);
#          dpm($one_filter->configuration['wisski_field'], "key");
          
          // see if it is a wisski field or not...
          if(isset($one_filter->configuration['wisski_field'])) {
            $query->condition($one_filter->configuration['wisski_field'], $one_filter->value, $one_filter->operator);
          } else {
            $query->condition($key, $one_filter->value, $one_filter->operator);
          }
        }
      }
    }    

#    dpm($filter_regex);

    $query->addMetaData('view', $view);
    $count_query->addMetaData('view', $view);

    // Add the query tags.
    if (!empty($this->options['query_tags'])) {
      foreach ($this->options['query_tags'] as $tag) {
        $query->addTag($tag);
        $count_query->addTag($tag);
      }
    }

    // if an aditional argument is set, look if it is the eid!    
    if(isset($view->build_info['substitutions'])) {
      $substitutions = $view->build_info['substitutions'];

      foreach($substitutions as $sbs_key => $sbs_value) {
        if(strpos($sbs_key, "eid")) {
          $entity_id = $sbs_value;
          // we dont break anymore... see if we can do something else
#          break;
        } else if(strpos($sbs_key, " arguments.wisski_path_") !== FALSE ) {

          // continue if it is not a number...
          if(!is_numeric($sbs_value))
            continue;

          // cut away the front part and the " }}" at the end
          $path_part = substr($sbs_key, strpos($sbs_key, " arguments.wisski_path_") + strlen("arguments.wisski_path_") +1, -3);


          if( strpos($path_part, "__") !== FALSE) {

            $pb_and_path = explode("__", $path_part, 2);
#            dpm($pb_and_path, "pbp?");

            if (count($pb_and_path) != 2) {
              drupal_set_message("Bad field id for Wisski views: $field", 'error');
            } else {

#          dpm($path_part, "pathpart");
#          dpm($sbs_value, "val");
              
              $query->condition($pb_and_path[0] . '.' . $pb_and_path[1], $sbs_value, "HAS_EID");
              $count_query->condition($pb_and_path[0] . '.' . $pb_and_path[1], $sbs_value, "HAS_EID");

#              dpm($query, "query?");
            }
            
          }
        
        }
      }
    }

    // check if the entity has the correct bundle
    if(!empty($entity_id)) {
      $bids = AdapterHelper::getBundleIdsForEntityId($entity_id, TRUE);
      
      $found = FALSE;
      foreach($bundle_ids as $bundleid) {
      
        // if we find it somewhere, set it to true.
        if(in_array($bundleid, $bids) != FALSE) {
          $found = TRUE;
        }
      
      }
      
      // did we find something?
      if(!$found) {
        // if not, early exit!
        return;
      }
      
 #     dpm($bids);
      
    }
    
//    dpm(serialize($substitutions), "yay!");
#    dpm($entity_id, "eid");
#    dpm($bundle_ids, "bundleids");
#    dpm($view, "view");
    $start = microtime(true);

    // if we are using the pager, calculate the total number of results
    if ($this->pager && $this->pager->usePager()) {
      try {
#        dpm(microtime(), "before count");
        //  Fetch number of pager items differently based on data locality.
        // Execute the local count query.
#        dpm($count_query->count, "count?");
#        $count_query->countQuery();
        $erg = $count_query->execute();
        
#        dpm($erg, "erg");
        if(is_array($erg))
          $erg = count($erg);
        
        $this->pager->total_items = $erg;
#        dpm($this->pager->total_items, "total");
#        dpm(microtime(), "after count");
        if (!empty($this->pager->options['offset'])) {
          $this->pager->total_items -= $this->pager->options['offset'];
        }

        $this->pager->updatePageInfo();
      }
      catch (\Exception $e) {
        if (!empty($view->simpletest)) {
          throw($e);
        }
        // Show the full exception message in Views admin.
        if (!empty($view->live_preview)) {
          drupal_set_message($e->getMessage(), 'error');
        }
        else {
          drupal_set_message("Exception: " . $e->getMessage());
          // vpr does not exist?
          #vpr('Exception in @human_name[@view_name]: @message', array('@human_name' => $view->human_name, '@view_name' => $view->name, '@message' => $e->getMessage()));
        }
        return;
      }
    }

//    dpm($this->pager->total_items, "total");
#    dpm(microtime(), "begin pre-exe");
    // Let the pager set limit and offset.
    if ($this->pager) {
      $this->pager->preExecute($query);
    }
    
    // early opt out in case of no results
    if($this->pager->usePager() && $this->pager->total_items == 0) {
      $view->result = [];
      $view->execute_time = microtime(true) - $start;

      return;
    }

    if($this->orderby) {
      foreach($this->orderby as $elem) {
        $query->sort($elem['field'], $elem['direction']);
      }
    }
 #   dpm(microtime(), "sec!");
    if (!empty($this->limit) || !empty($this->offset)) {
      // We can't have an offset without a limit, so provide a very large limit instead.
      $limit  = intval(!empty($this->limit) ? $this->limit : 999999999);
      $offset = intval(!empty($this->offset) ? $this->offset : 0);

      // Set the range for the query.
      // Set the range on the local query.
      $query->range($offset, $limit);
    }
#    dpm(microtime(), "end pre-exe");
    $view->result = array();
    try {
#      dpm(microtime(), "before ex");
 #     dpm($query, "query");
      // Execute the local query.
      $entity_ids = $query->execute();
#      dpm(microtime(), "after ex");
#      dpm($entity_ids, "eids!");
            
      if (empty($entity_ids)) {
        $view->result = [];
      }
      else {
#        dpm(microtime(), "before frv");
        // Get the fields for each entity, give it its ID, and then add to the result array.
        // This is later used for field rendering
#        dpm($entity_ids, "eids");
        $values_per_row = $this->fillResultValues($entity_ids, $bundle_ids, $filter_regex);
#        dpm(microtime(), "after frv");
#dpm([$values_per_row, $entity_ids], __METHOD__);
        foreach ($values_per_row as $rowid => $values) {
          $row = new ResultRow($values);
          $row->index = $rowid;
          $view->result[] = $row;
        }
      }
      
      if ($this->pager) {
        $this->pager->postExecute($view->result);
        if ($this->pager->usePager()) {
          $view->total_rows = $this->pager->getTotalItems();
        }
      }
    }
    catch (\Exception $e) {
      // Show the full exception message in Views admin.
#      if (!empty($view->preview)) {
        drupal_set_message($e->getMessage(), 'error');
#      }
#      else {
#        vpr('Exception in @human_name[@view_name]: @message', array('@human_name' => $view->human_name, '@view_name' => $view->name, '@message' => $e->getMessage()));
#      }
      return;
    }
#    dpm(microtime(), "thrd!");

#    dpm(microtime(), "end execute");

    $view->execute_time = microtime(true) - $start;
#wisski_tick("end exec views");
  }

  
  protected function fillResultValues($entity_ids, $bundle_ids = array(), $filter_regex = array()) {
 #   dpm($bundle_ids, "this");

    $eid_to_uri_per_aid = [];


    // we must not load the whole entity unless explicitly wished. this is way too costly!
#    dpm(microtime(), "beginning of fill result values");
    $values_per_row = [];
    // we always return the entity id
    foreach ($entity_ids as $entity_id) {
      $values_per_row[$entity_id] = ['eid' => $entity_id];
    }

    $fields = $this->fields;

    // store here only fields that may be attached to the entity.
    // typically our "wisski-path-special-fields" for the view may
    // not be attached. 
    $pseudo_entity_fields = array();
    

    #dpm($fields);
#    dpm(serialize($values_per_row));
    
#rpm($this->fields, "fields");
#    dpm(microtime(), "before load");
    // Mark: this mechanism seems to be rather slow and it is overpacing
    // for many systems.
    // so we make it smaller here... we don't simply load everything
    // but we try to create the entity in the end and throw it away again.
    // the storage already does this anway...

#    $ids_to_load = array();
/*
    if (isset($fields['_entity'])) {
      foreach ($values_per_row as &$row) {
#        $ids_to_load[] = $row['eid'];
        $row['_entity'] = entity_load('wisski_individual', $row['eid']);
 #       dpm(serialize($row['_entity']), "entity");
      }
    }
*/  
/*
    $loaded_ids = entity_load_multiple('wisski_individual', $ids_to_load);
#    dpm(serialize($ids_to_load));
#    dpm(serialize(entity_load(437)));
#    dpm(serialize($loaded_ids));
    if (isset($fields['_entity'])) {
      foreach ($values_per_row as &$row) {
#        dpm($row);
#        $row['_entity'] = entity_load('wisski_individual', $row['eid']);;
#        $bid = reset($bundle_ids);
#        $row['_entity'] = entity_create('wisski_individual', array('eid' => $row['eid'], 'bundle' => $bid));
#        dpm($row['_entity'], "ent");
        $row['_entity'] = $loaded_ids[$row['eid']];
      }
    }
    */
#    dpm(serialize($values_per_row));

#    dpm($row, "row");

    if(isset($fields['_entity'])) {   
      $do_dummy_load = $fields['_entity'];
    } else
      $do_dummy_load = FALSE;
    
#    dpm(microtime(), "after load");
    
    unset($fields['eid']);

    if(isset($fields['_entity']))
      unset($fields['_entity']);
  
    $pb_cache = array();
    $path_cache = array();
    

    while (($field = array_shift($fields)) !== NULL) {
#      dpm(microtime(), "beginning one thing");
      if ($field == 'title') {
#        dpm(microtime(), "before generate");
        if(!empty($bundle_ids))
          $bid = reset($bundle_ids);
        else
          $bid = NULL;
        
        foreach ($values_per_row as $eid => &$row) {

          if(empty($row['bundle'])) {
            $row['bundle'] = $bid;
            $pseudo_entity_fields[$eid]['bundle'] = $row['bundle'];
          }

          $row['title'] = wisski_core_generate_title($eid, NULL, FALSE, $bid);
          $pseudo_entity_fields[$eid]['title'] = $row['title'];
        }
        
#        dpm(microtime(), "after generate title");
      }
      elseif ($field =='preferred_uri') {
        $localstore = AdapterHelper::getPreferredLocalStore();
        if ($localstore) {
          // By Mark: I am not entirely sure, if I want to create a uri here...
          $values_per_row[$eid]['preferred_uri'] = AdapterHelper::getUrisForDrupalId($eid, $localstore, TRUE);
        }
        else {
          $values_per_row[$eid]['preferred_uri'] = '';
        }
      }
      elseif ($field == 'preview_image') {
#        dpm("prew");
#        dpm(microtime(), "beginning image prev");        
#        dpm(\Drupal::entityTypeManager()->getStorage('wisski_individual'));
#        return;
        // prepare the listbuilder for external access.
        \Drupal::entityTypeManager()->getStorage('wisski_individual')->preparePreviewImages();
        
        foreach($values_per_row as $eid => &$row) {
#          dpm(microtime(), "ar " . serialize($bundle_ids));
          #$preview_image = WisskiCacheHelper::getPreviewImageUri($eid);
          if(empty($bundle_ids)) {
#            dpm("i have no bundle!");
            $bids = AdapterHelper::getBundleIdsForEntityId($row['eid'], TRUE);
          } else { // take the ones we have before.
            $bids = $bundle_ids;
          }

          $bid = reset($bids);
          if(empty($row['bundle'])) {
            $row['bundle'] = $bid;
            $pseudo_entity_fields[$eid]['bundle'] = $row['bundle'];  
          }
          
#          dpm(microtime(), "br");          
          $preview_image_uri = \Drupal::entityTypeManager()->getStorage('wisski_individual')->getPreviewImageUri($eid,$bid);
#          dpm(microtime(), "brout");          

          if(strpos($preview_image_uri, "public://") !== FALSE) {
            $preview_image_uri = str_replace("public:/", \Drupal::service('stream_wrapper.public')->baseUrl(), $preview_image_uri);
          }

          global $base_path;
          $row['preview_image'] = '<a href="' . $base_path . 'wisski/navigate/'.$eid.'/view?wisski_bundle='.$bid.'"><img src="'. $preview_image_uri .'" /></a>';
          $pseudo_entity_fields[$eid]['preview_image'] = $row['preview_image'];
        }
#        dpm(microtime(), "after preview image");
      }
      elseif ($field == 'bundle' || $field == 'bundle_label' || $field == 'bundles') {
#        dpm($values_per_row, "vpr");
        foreach ($values_per_row as $eid => &$row) {
          if(empty($bundle_ids))
            $bids = AdapterHelper::getBundleIdsForEntityId($row['eid'], TRUE);
          else
            $bids = $bundle_ids;
          $row['bundles'] = $bids;
          $bid = reset($bids);  // TODO: make a more sophisticated choice rather than the first one
          $row['bundle'] = $bid;
          $bundle = entity_load('wisski_bundle', $bid);
          $row['bundle_label'] = $bundle->label();
          $pseudo_entity_fields[$eid]['bundle'] = $row['bundle'];
          $pseudo_entity_fields[$eid]['bundles'] = $row['bundles'];
          $pseudo_entity_fields[$eid]['bundle_label'] = $row['bundle_label'];
        }
#        dpm(microtime(), "after bundles");
      }
      elseif (strpos($field, "wisski_path_") === 0 && strpos($field, "__") !== FALSE) {
        
        // the if is rather a hack but currently I have no idea how to access
        // the field information wisski_field from WisskiEntityViewsData.
        
        $pb_and_path = explode("__", substr($field, 12), 2);
        if (count($pb_and_path) != 2) {
          drupal_set_message("Bad field id for Wisski views: $field", 'error');
        }
        else {
        
          $moduleHandler = \Drupal::service('module_handler');
          if (!$moduleHandler->moduleExists('wisski_pathbuilder')){
            return NULL;
          }
                            
          if(isset($pb_cache[$pb_and_path[0]]))
            $pb = $pb_cache[$pb_and_path[0]];
          else
            $pb = entity_load('wisski_pathbuilder', $pb_and_path[0]);
          
          $pb_cache[$pb_and_path[0]] = $pb;
            
          if(isset($path_cache[$pb_and_path[1]]))
            $path = $path_cache[$pb_and_path[1]];
          else
            $path = entity_load('wisski_path', $pb_and_path[1]);
 
          $path_cache[$pb_and_path[1]] = $path;
            
          if (!$pb) {
            drupal_set_message("Bad pathbuilder id for Wisski views: $pb_and_path[0]", 'error');
          }
          elseif (!$path) {
            drupal_set_message("Bad path id for Wisski views: $pb_and_path[1]", 'error');
          }
          else {
          
            $pbp = $pb->getPbPath($path->getID());
            $field_to_check = $pbp['field'];
            
            if($field_to_check != $field)
              $no_entity_field[] = $field;
            
            $first_row = current($values_per_row);
            
          
#            dpm($values_per_row[$eid]['bundle']);
            $field_def = \Drupal::service('entity_field.manager')->getFieldMap();#->getFieldDefinitions('wisski_individual',$values_per_row[$eid]['bundle']);
            $fieldmap = \Drupal::service('entity_field.manager')->getFieldMap();

            $is_file = FALSE;

            // get the main property name             
            if(!empty($fieldmap) && isset($fieldmap['wisski_individual']) && isset($fieldmap['wisski_individual'][$field_to_check]) && isset($fieldmap['wisski_individual'][$field_to_check]['bundles'])) {
              $fbundles = $fieldmap['wisski_individual'][$field_to_check]['bundles'];
#                    dpm(current($fbundles), "fb");
            
              $field_def = \Drupal::service('entity_field.manager')->getFieldDefinitions('wisski_individual',current($fbundles));

#              dpm(serialize($field_def[$field_to_check]->getFieldStorageDefinition()->getDependencies()), "def");
              
              $is_file = in_array('file',$field_def[$field_to_check]->getFieldStorageDefinition()->getDependencies()['module']);
              
              $main_prop = $field_def[$field_to_check]->getFieldStorageDefinition()->getMainPropertyName();
#              dpm($main_prop, "found it! for field " . $field_to_check);
            } else {
              $main_prop = "value";
#              dpm($main_prop, "did not find it " . $field_to_check);
            }

#            dpm($main_prop, "main prop!");            
#            dpm($realfield, "realfield");
#                    dpm(\Drupal::service('entity_field.manager')->getFieldMap(), "fieldmap");
#                    $field_def = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();
#                    dpm(serialize($field_def[$realfield]), "fdef");
#                    dpm($field_def[$realfield]->getFieldStorageDefinition()->getMainPropertyName(), "mp!");
            

            // skip the fields that we already loaded...            
#            if($first_row['_entity']->$field_to_check) {
#              dpm($field_to_check, "I am not checking");
#              continue;
//              dpm(serialize($first_row['_entity']->$field_to_check), "field to check!!!");
#            }
            
#            dpm($field_to_check, "I am checking");

            // this is the old mode... basically we want to ask any adapter :/          
//            $adapter = entity_load('wisski_salz_adapter', $pb->getAdapterId());
            $adapters = entity_load_multiple('wisski_salz_adapter');
            
            foreach($adapters as $adapter) {
            $aid = $adapter->id();
            if (!$adapter) {
              drupal_set_message("Bad adapter id for pathbuilder $pb_and_path[0]: " . $pb->getAdapterId(), 'error');
            }
            else {
              $engine = $adapter->getEngine();
              if (!($engine instanceof Sparql11EngineWithPB)) {
                // lets just hope it can handle it somehow...
                // @todo - this is not funny!!!
                continue;
//                drupal_set_message("Adapter cannot be queried by path in WissKI views for path " . $path->getName() . " in pathbuilder " . $pb->getName(), 'error');
              }
              else {
                // we need to distinguish references and data primitives
                $is_reference = $path->getDatatypeProperty() == 'empty';
                $out_prop = 'out';
                $disamb = NULL;
                if ($is_reference) {
                  $disamb = $path->getDisamb();
                  if ($disamb < 2) $disamb = count($path->getPathArray());
                  // NOTE: $disamb is the concept position (starting with 1)
                  // but generateTriplesForPath() names vars by concept 
                  // position times 2, starting with 0!
                  $disamb = 'x' . (($disamb - 1) * 2);
                  $out_prop = NULL;
                } else {
                  $disamb = $path->getDisamb();
                  if(!empty($disamb)) {
                    $disamb = 'x' . (($disamb - 1) * 2);
                  }
                }

#                dpm($pbp);
#                $starting_position = $pb->getRelativeStartingPosition($pbp['parent'], FALSE);
#                dpm($starting_position, "start");
                
                $select = "SELECT DISTINCT ?x0 ";
                if(!empty($disamb))
                  $select .= '?' . $disamb . ' ';
                
                if(!empty($out_prop))
                  $select .= '?' . $out_prop . ' ';
                
                $select .= " WHERE { VALUES ?x0 { ";
                  
                $uris_to_eids = []; // keep for reverse mapping of results
                foreach ($entity_ids as $eid) {
                  if (isset($eid_to_uri_per_aid[$aid]) && isset($eid_to_uri_per_aid[$aid][$eid])) {
                    $uri = $eid_to_uri_per_aid[$aid][$eid];
                  } 
                  else {
                    $uri = $engine->getUriForDrupalId($eid, FALSE);
                    if ($uri) {
                      if (!isset($eid_to_uri_per_aid[$aid])) {
                        $eid_to_uri_per_aid[$aid] = [];
                      }
                      $eid_to_uri_per_aid[$aid][$eid] = $uri;
                    }
                    else {
                      continue;
                    }
                  }
                  $select .= "<$uri> ";
                  $uris_to_eids[$uri] = $eid;
                }
                $select .= "} ";
                // NOTE: we need to set the $relative param to FALSE. All other
                // optional params should be default values
                $select .= $engine->generateTriplesForPath($pb, $path, "", NULL, NULL, 0, 0, FALSE, '=', 'field', FALSE);
                #$select .= "}";

                // add filter criteria on this level
                // because these paths must not align with entities.
#                if(isset($filter_regex[$field])) {
#                  foreach($filter_regex[$field] as $filter_val) {
#                    $select .= "FILTER REGEX(?out, '" . $filter_val['val'] . "', 'i') . ";
#                  }
#                }

                $select .= "}";

                #dpm($select, "select " . $path->getID() .': '.$path->getDatatypeProperty() . " on " . $adapter->id() );
#                dpm(microtime(), "before");
                $result = $engine->directQuery($select);

               #dpm([$select, $result], 'select' . $path->getID());

#                dpm(microtime(), "after");
                foreach ($result as $sparql_row) {
                  if (isset($uris_to_eids[$sparql_row->x0->getUri()])) {
#                    dpm($uris_to_eids[$sparql_row->x0->getUri()], $sparql_row->x0->getUri());
                    $eid = $uris_to_eids[$sparql_row->x0->getUri()];

/*                    
                    $pbp = $pb->getPbPath($path->getID());
                    $realfield = $pbp['field'];
                    dpm($values_per_row[$eid]['bundle']);
#                    $field_def = \Drupal::service('entity_field.manager')->getFieldMap();#->getFieldDefinitions('wisski_individual',$values_per_row[$eid]['bundle']);
                    $fieldmap = \Drupal::service('entity_field.manager')->getFieldMap();
                    
                    $fbundles = $fieldmap['wisski_individual'][$realfield]['bundles'];
#                    dpm(current($fbundles), "fb");
                    
                    $field_def = \Drupal::service('entity_field.manager')->getFieldDefinitions('wisski_individual',current($fbundles));
                    dpm($realfield, "realfield");
                    dpm(\Drupal::service('entity_field.manager')->getFieldMap(), "fieldmap");
#                    $field_def = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();
                    dpm(serialize($field_def[$realfield]), "fdef");
                    dpm($field_def[$realfield]->getFieldStorageDefinition()->getMainPropertyName(), "mp!");
*/
#                    $field_ob = \Drupal\field\Entity\FieldConfig::load($realfield);
#                    dpm($field_ob->getFieldStorageDefinition()->getMainPropertyName(), "yay!");	
#                    dpm($pbp, "realfield!");
#                    dpm($eid, "eid!!");
#                    dpm($is_reference, "is ref");
                    if (!$is_reference && (!isset($sparql_row->$out_prop) || $sparql_row->$out_prop === NULL)) {
                      \Drupal::logger('WissKI views')->warning("invalid reference slot {s} for path {pid}", ['s' => $out_prop, 'pid' => $path->getID()]);
                    }
                    elseif ($is_reference) {
#                      dpm($disamb, "yuhu!");
                      $referenced_uri = $sparql_row->$disamb->getUri();
#                      dpm($referenced_uri);
                      $referenced_eid = AdapterHelper::getDrupalIdForUri($referenced_uri);
#                      dpm($referenced_eid);
                      $referenced_title = wisski_core_generate_title($referenced_eid);
#                      dpm($referenced_title);
                      $values_per_row[$eid][$field][] = array('value' => $referenced_title, 'target_id' => $referenced_eid, 'wisskiDisamb' => $referenced_uri);
                      // duplicate the information to the field for the entity-management
                      $values_per_row[$eid][$field_to_check][] = array('value' => $referenced_title, 'target_id' => $referenced_eid, 'wisskiDisamb' => $referenced_uri);
                      #$values_per_row[$eid][$field][] = $referenced_eid;
                    }
                    else {
                      if(!empty($disamb)) {
                        if(!empty($is_file)) {
                          drupal_set_message("On your image path there is a disamb set. How do you think the system now should behave? Make the image clickable or what?!", "warning");
                        }
#                          $storage = \Drupal::entityTypeManager()->getStorage('wisski_individual');
#                          $val = $storage->getFileId($sparql_row->$out_prop->getValue());
#                          // in case of files: throw the disamb away!
#                          $values_per_row[$eid][$field][] = array($main_prop => $val);
#                          $values_per_row[$eid][$field_to_check][] = array($main_prop => $val);
#                        } else {
                        $values_per_row[$eid][$field][] = array($main_prop => $sparql_row->$out_prop->getValue(), 'wisskiDisamb' => $sparql_row->$disamb->getUri());
                        $values_per_row[$eid][$field_to_check][] = array($main_prop => $sparql_row->$out_prop->getValue(), 'wisskiDisamb' => $sparql_row->$disamb->getUri());
#                        }
                      } else {
#                        dpm(serialize($is_file), "is file!!");
                        if(!empty($is_file)) {
                          $storage = \Drupal::entityTypeManager()->getStorage('wisski_individual');
                          $val = $storage->getFileId($sparql_row->$out_prop->getValue());
                          $values_per_row[$eid][$field][] = array($main_prop => $val);
                          $values_per_row[$eid][$field_to_check][] = array($main_prop => $val);
                        } else {
                          $values_per_row[$eid][$field][] = array($main_prop => $sparql_row->$out_prop->getValue());
                          $values_per_row[$eid][$field_to_check][] = array($main_prop => $sparql_row->$out_prop->getValue());
                        }
                      }
                    }
                    $pseudo_entity_fields[$eid][$field_to_check] = $values_per_row[$eid][$field_to_check];
#                    $entity_dump[$eid] = \Drupal::entityManager()->getStorage('wisski_individual')->addCacheValues(array($values_per_row[$eid]), $values_per_row);
                    
#                    dpm($values_per_row[$eid]);
                  }
                }
#if ($field == 'wisski_path_sammlungsobjekt__91') rpm([$path, $result, $values_per_row], '91');
              }
            }
            }
          }
        }
#        dpm(microtime(), "after field");
      
      }
    

    }
#    dpm(serialize($values_per_row));
#    return;
#    dpm(microtime(), "end of ...");    

    if ($do_dummy_load) {
      foreach ($values_per_row as $lkey => &$row) {
        // if we don't have a bundle we're in danger zone!
        if(empty($row['bundle'])) {
#          dpm("empty!");
          $bids = AdapterHelper::getBundleIdsForEntityId($lkey, TRUE);
          
          $row['bundles'] = $bids;
          $bid = reset($bids);  // TODO: make a more sophisticated choice rather than the first one
          $row['bundle'] = $bid;
          $pseudo_entity_fields[$lkey]['bundles'] = $values_per_row[$lkey]['bundles'];
          $pseudo_entity_fields[$lkey]['bundle'] = $values_per_row[$lkey]['bundle'];
        }
        
        // compatibility for old systems like herbar...
        if(!isset($pseudo_entity_fields[$lkey]['eid']))
          $pseudo_entity_fields[$lkey]['eid'] = array('value' => $lkey);
#        dpm($row);
#        $row['_entity'] = entity_load('wisski_individual', $row['eid']);;
#        $bid = reset($bundle_ids);
#        $tmp = entity_create('wisski_individual', $row);
#        dpm($pseudo_entity_fields, "psd");
        $entities = \Drupal::entityManager()->getStorage('wisski_individual')->addCacheValues(array($lkey => $lkey), $pseudo_entity_fields);
#        foreach($row as $field_name => $data) {
#          $entities[$lkey]->$field_name = $data;
#        }
#        dpm(serialize($entities), "ent");
        $row['_entity'] = $entities[$lkey];#\Drupal::entityManager()->getStorage('wisski_individual')->addCacheValues(array($values_per_row[$eid]), $values_per_row);
#        $row['_entity'] = entity_load('wisski_individual', $row['eid']);
#        $row['_entity'] = entity_create('wisski_individual', $row);
#        dpm($row, "row");
#        dpm(serialize($row['_entity']), "ent");
#        $row['_entity'] = $loaded_ids[$row['eid']];
#      dpm($row['_entity']->id(), "entity");
      }
    }

#    dpm(microtime(), "after end of ...");

#    dpm($values_per_row, "vpr");

#    return;

    return array_values($values_per_row);

  }

  public function placeholder() {
#    dpm(serialize($this), "error");
    return "wisski.placeholder" . $this->paramcount++;
  }

}
