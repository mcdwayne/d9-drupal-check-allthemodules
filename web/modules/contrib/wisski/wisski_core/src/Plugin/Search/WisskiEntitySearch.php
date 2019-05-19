<?php

namespace Drupal\wisski_core\Plugin\Search;

//use Drupal\search\Plugin\SearchInterface;
use Drupal\search\Plugin\SearchPluginBase;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\wisski_core\WisskiCacheHelper;

use Drupal\wisski_core\WisskiHelper;

/**
 * @SearchPlugin(
 *   id = "wisski_individual_search",
 *   title = @Translation("Wisski Entities"),
 * )
 */
class WisskiEntitySearch extends SearchPluginBase {
  
  /**
   * Maximum number of bundles to show on initial page
   */
  private $bundle_limit = 16;
  
  /**
   * Maximum number of paths to show for each bundle
   */
  private $path_limit = 10;

/*
  public function buildResults() {
    $built = parent::buildResults();
    dpm($this, "sis");
    dpm($built, "yay!");

    return $built;
  }
*/
  /**
   * Execute the search.
   *
   * This is a dummy search, so when search "executes", we just return a dummy
   * result containing the keywords and a list of conditions.
   *
   * @return array
   *   A structured list of search results
   */
  public function execute() {
    
#    dpm($this,__METHOD__);
    $results = array();
    if ($this->isSearchExecutable()) {
      $parameters = $this->getParameters();
      
      if (isset($parameters['entity_title']) && $string = $parameters['entity_title']) {
        
        $select = \Drupal::service('database')
            ->select('wisski_title_n_grams','w');
        $rows = $select
            ->fields('w', array('ent_num', 'bundle'))
            ->condition('ngram', "%" . $select->escapeLike($string) . "%", 'LIKE')
            ->condition('bundle', $parameters['bundles'], 'IN')
            ->execute()
            ->fetchAll();
        
        foreach ($rows as $row) {
          $results[$row->bundle][] = $row->ent_num;
        }

      } else {
        foreach ($parameters['bundles'] as $bundle_id) {
          if (!isset($parameters[$bundle_id])) continue;
          
          $query = \Drupal::entityQuery('wisski_individual');
          $query->setPathQuery();
          switch ($parameters[$bundle_id]['query_type']) {
            case 'AND': 
              $group = $query->andConditionGroup();
              break;
            case 'OR': 
            default: 
              $group = $query->orConditionGroup();
          }
  // we don't need to set the bundle as condition
  // the bundle is already an implicit condition through
  // the paths
  //        $qroup = $group->condition('bundle',$bundle_id);
          foreach ($parameters[$bundle_id]['paths'] as list($path_id,$search_string,$operator)) {
            //dpm($operator.' '.$search_string,'Setting condition');
            $group = $group->condition($path_id,$search_string,$operator);
          }
          $query->condition($group);
          #dpm($query);
          $results[$bundle_id] = $query->execute();
        }
      }
    }
    $return = array();
    foreach ($results as $bundle_id => $entity_ids) {
      $bundle = entity_load('wisski_bundle', $bundle_id);
      foreach ($entity_ids as $entity_id) {
        // we also give the bundle id as we know it here and the bundle id is
        // mandatory for title creation and there my be cases
        // in which neither a title nor a bundle id have been cached for the
        // entity. this would prevent WissKI from generating and displaying the
        // right title.
        $title = wisski_core_generate_title($entity_id, NULL, FALSE, $bundle_id);
        #$preview = getPreviewImageUri($entity_id, $bundle_id);
#        $preview = WisskiCacheHelper::getPreviewImageUri($entity_id);
#        dpm($preview, "prev");
        if (is_null($title)) $title = $entity_id;
        $return[] = array(
#          'snippet' => '<img src="' . $preview . '">',
          'link' => Url::fromRoute(
            'entity.wisski_individual.canonical',
            array('wisski_individual' => $entity_id),
            array('query' => array('wisski_bundle' => $bundle_id))
          )->toString(),
          'type' => is_null($bundle) ? '' : $bundle->label(),
          'title' => $title,
          'entity_id' => $entity_id,
          'bundle_id' => $bundle_id,
        );
      }
    }
#    dpm($return, "yay!!");
    return $return;
  }

  public function searchFormAlter(array &$form, FormStateInterface $form_state) {
  
    //dpm($form,__FUNCTION__);
    //dpm($this,__METHOD__);
    unset($form['basic']);
    unset($form['help_link']);

    if (!empty($_GET)) $defaults = $_GET;
    elseif (!empty($_POST)) $defaults = $_POST;
    //if (isset($defaults)) dpm($defaults,'Defaults');
    /*
    $form['entity_title'] = array(
      '#type' => 'textfield',
      '#autocomplete_route_name' => 'wisski.titles.autocomplete',
      '#autocomplete_route_parameters' => isset($selected_bundles) ? array('bundles' => $selected_bundles) : array(),
      '#default_value' => '',
      '#title' => $this->t('Search by Entity Title'),
      '#description' => $this->t('Finds titles from the cache table'),
      '#attributes' => array('placeholder' => $this->t('Entity Title')),
    );
    */
    $storage = $form_state->getStorage();
    $paths = (isset($storage['paths'])) ? $storage['paths']: array();
    $input = $form_state->getUserInput();
    //dpm($input,'User input');
    if (isset($input['advanced']['bundles']['select_bundles'])) {
      $selection = $input['advanced']['bundles']['select_bundles'];
    } else $selection = array();
    if (isset($storage['options'])) {
      $options = $storage['options'];
      $trigger = $form_state->getTriggeringElement();
      if ($trigger['#name'] == 'btn-add-bundle') {    
        $new_bundle = $input['advanced']['bundles']['auto_bundle']['input_field'];
        $matches = array();
        if (preg_match('/^(.+)\s\((\w+)\)$/',$new_bundle,$matches)) {
          list(,$label,$id) = $matches;
          if (!isset($options[$id])) $options[$id] = $label;
          $bundle = \Drupal\wisski_core\Entity\WisskiBundle::load($id);
          $paths[$id] = $bundle->getPathOptions();
        }
      }
    } else {
      // don't load only bundle_limit amount of bundles
      #$bundle_ids = \Drupal::entityQuery('wisski_bundle')->range(0,$this->bundle_limit)->execute();
      // load all
      $bundle_ids = \Drupal::entityQuery('wisski_bundle')->execute();
      
      // now filter them again
      // get all top groups from pbs
      $parents = \Drupal\wisski_core\WisskiHelper::getTopBundleIds();
        
      // only show top groups
      foreach($bundle_ids as $bundle_id => $label) {
        if(!in_array($bundle_id, $parents))
          unset($bundle_ids[$bundle_id]);
      }
                            
      if (isset($defaults['bundles'])) $bundle_ids = array_unique(array_merge($bundle_ids,array_values($defaults['bundles'])));
      $bundles = \Drupal\wisski_core\Entity\WisskiBundle::loadMultiple($bundle_ids);
      $options = array();
      $selection = array();
      foreach($bundles as $bundle_id => $bundle) {
        $options[$bundle_id] = $bundle->label();
        $paths[$bundle_id] = $bundle->getPathOptions();
        if (isset($defaults['bundles'][$bundle_id])) $selection[$bundle_id] = $bundle_id;
        else $selection[$bundle_id] = 0;
      }
    }
    $bundle_count = \Drupal::entityQuery('wisski_bundle')->count()->execute();
    
#    dpm($selection, "sel!");
    
    $form['entity_title'] = array(
      '#type' => 'textfield',
      '#autocomplete_route_name' => 'wisski.titles.autocomplete',
      '#autocomplete_route_parameters' => isset($selection) ? array('bundles' => $selection) : array(),
      '#default_value' => '',
      '#title' => $this->t('Search by Entity Title'),
      '#description' => $this->t('Finds titles from the cache table'),
      '#attributes' => array('placeholder' => $this->t('Entity Title')),
    );
    
    #dpm($selection,'selection');
    $storage['paths'] = $paths;
    //dpm($paths,'Paths');
    $storage['options'] = $options;
    $form_state->setStorage($storage);
    $form['advanced'] = array(
      '#type' => 'details',
      '#tree' => TRUE,
      '#title' => $this->t('Advanced Search'),
      '#open' => isset($defaults['bundles']),
    );
    /*
    $form['advanced']['keys'] = array(
      '#type' => 'search',
      '#title' => $this->t('Search for keywords'),
      '#size' => 60,
    );
    */
    $form['advanced']['bundles'] = array(
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('in Bundles')
    );
    $form['advanced']['bundles']['select_bundles'] = array(
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $selection,
      '#prefix' => '<div id = wisski-search-bundles>',
      '#suffix' => '</div>',
      '#ajax' => array(
        'wrapper' => 'wisski-search-paths',
        'callback' => array($this,'replacePaths'),
      ),
    );
    if ($bundle_count > $this->bundle_limit) {
      $form['advanced']['bundles']['auto_bundle'] = array(
        '#type' => 'container',
        '#attributes' => array('class' => 'container-inline','title' => $this->t('Find more Bundles')),
        
      );
      $form['advanced']['bundles']['auto_bundle']['input_field'] = array(
        '#type' => 'entity_autocomplete',
        '#target_type' => 'wisski_bundle',
        '#size' => 48,
        '#attributes' => array('placeholder' => $this->t('Bundle Name')),
      );
      $form['advanced']['bundles']['auto_bundle']['add_op'] = array(
        '#type' => 'button',
        '#value' => '+',
        '#limit_validation_errors' => array(),
        '#ajax' => array(
          'wrapper' => 'wisski-search-bundles',
          'callback' => array($this,'replaceSelectBoxes'),
        ),
        '#name' => 'btn-add-bundle',
      );
    } else $form['advanced']['bundles']['auto_bundle']['#type'] = 'hidden';
    //dpm(array($selection,$paths));
    $selection = array_filter($selection);
    $selected_paths = array_intersect_key($paths,$selection);
    $form['advanced']['paths'] = array(
      '#type' => 'hidden',
      '#prefix' => '<div id=wisski-search-paths>',
      '#suffix' => '</div>',
      '#tree' => TRUE,
      '#title' => $this->t('in Paths'),
    );
    if (!empty($selected_paths)) {
      $form['advanced']['paths']['#type'] = 'fieldset';
      foreach ($selected_paths as $bundle_id => $bundle_paths) {
        $bundle_path_options = array();
        $form['advanced']['paths'][$bundle_id] = array(
          '#type' => 'fieldset',
          '#tree' => TRUE,
          '#title' => $options[$bundle_id],
        );
#        dpm($bundle_paths, "bps"); 
        foreach ($bundle_paths as $pb => $pb_paths) {
          if (is_string($pb_paths)) {
            //this is a global pseudo-path like 'uri'
            $bundle_path_options[$pb] = $pb_paths;
            if (isset($defaults[$bundle_id]['paths'][$pb])) $bundle_path_defaults[$pb] = $defaults[$bundle_id]['paths'][$pb];
          } else {
            foreach ($pb_paths as $path_id => $path_label) {
              $bundle_path_options[$path_id] = "$path_label ($path_id)";
            }
          }
        }
        if (isset($defaults[$bundle_id]['paths'])) $bundle_path_defaults = $defaults[$bundle_id]['paths'];
        else $bundle_path_defaults = array();
        //dpm($bundle_path_defaults,'defaults '.$bundle_id);
#        dpm($bundle_path_options, "bpo");
        for ($i = 0; $i < $this->path_limit && $i < count($bundle_path_options); $i++) {
          #$list = each($bundle_path_defaults);
          #dpm($list, "old");
          if(!empty(current($bundle_path_defaults)))
            $list = [key($bundle_path_defaults), current($bundle_path_defaults)];
          else
            $list = NULL;
          next($bundle_path_defaults);
#          dpm($list, "new");
          
          $def_input = '';
          $def_operator = $this->getDefaultOperator();
//          dpm($list, "list");
          if ($list) {
            list( , list($path_id, $def_input, $def_operator)) = $list;
          } else {
//            $list = each($bundle_path_options);
            $list = [key($bundle_path_options), current($bundle_path_options)];
            next($bundle_path_options);
            
            if ($list) list($path_id) = $list;
          }
          if ($list !== FALSE) {
            $form['advanced']['paths'][$bundle_id][$i] = array(
              '#type' => 'container',
              '#attributes' => array('class' => 'container-inline', 'data-wisski' => $bundle_id.'.'.$i),
              '#tree' => TRUE,
              'path_selection' => array(
                '#type' => 'select',
                '#options' => $bundle_path_options,
                '#default_value' => $path_id,
                '#weight' => 1,
              ),
              'operator' => array(
                '#type' => 'select',
                '#options' => $this->getSearchOperators(),
                '#default_value' => $def_operator,
                '#weight' => 2,
              ),
              'input_field' => array(
                '#type' => 'textfield',
                '#default_value' => $def_input,
                '#size' => 30,
                '#weight' => 3,
              ),
              '#element_validate' => array(array($this,'validateChoice')),
            );
          }
        }
        $form['advanced']['paths'][$bundle_id]['query_type'] = array(
          '#type' => 'container',
          '#attributes' => array('class' => 'container-inline'),
          'selection' => array(
            '#type' => 'radios',
            '#options' => array('AND' => $this->t('All'),'OR' => $this->t('Any')),
            '#default_value' => isset($defaults[$bundle_id]['query_type']) ? $defaults[$bundle_id]['query_type'] : 'AND',
            '#title' => $this->t('Match'),
          ),
        );
      }
    }
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#name' => 'standard-submit',
      '#value' => $this->t('Search Wisski Entities'),
    );
    
    // make a nice export button
    $form['actions']['export'] = array(
      '#name' => 'excel_export',
      '#type' => 'image_button',
      '#title' => 'Export to Excel',
      '#src' => drupal_get_path('module', 'wisski_core') . "/images/export_excel.png",
      '#attributes' => [ 'alt' => t('Export to Excel'), ],
      '#op' => 'wisski_core_excel_export',
#      '#ajax' => [
#        '#callback' => [ 'wisski_excel_export' ],
#      ],
      '#submit' => array('wisski_core_excel_export', "data" => $this->execute()),
#      '#prefix' => '<p>',
#      '#suffix' => '</p>',
    );
                              
    //dpm($form);
  }

  protected function getDefaultOperator() {
    return 'CONTAINS';
  }


  protected function getSearchOperators() {
  
    return array(
      'CONTAINS' => $this->t('contains'),
      '=' => $this->t('exactly'),
      '<>' => $this->t('not equal'),
      '>' => '>',
#      '>=' => '>=',
      '<' => '<',
#      '<=' => '<=',
      'STARTS_WITH' => $this->t('starts with'),
      'ENDS_WITH' => $this->t('ends with'),
      'NOT_EMPTY' => $this->t('not empty'),
      'EMPTY' => $this->t('empty'),
#      'ENDS_WITH' => $this->t('Ends with'),
#      'ALL' => $this->t('all of'),
#      'IN' => $this->t('one of'),
#      'NOT_IN' => $this->t('none of'),
#      'BETWEEN' => $this->t('between'),
    );
    
  }
  
  public function validateChoice(array $element, FormStateInterface $form_state, array $form) {
    // in case of excel export, skip.
    if($form_state->getTriggeringElement()['#name'] == "excel_export")
      return TRUE;
         
#    dpm(func_get_args(),__METHOD__);
    list($bundle_id,$row_num) = explode('.',$element['#attributes']['data-wisski']);
    $vals = $form_state->getValue(array('advanced','paths',$bundle_id,$row_num));
    $input = $vals['input_field'];
    switch ($vals['operator']) {
      case '=':
      case '<>':
      case '>':
      case '>=':
      case '<':
      case '<=':
      case 'STARTS_WITH':
      case 'CONTAINS':
      case 'ENDS_WITH': {    
        if (!empty($input) && strlen($input) < 1) {
          $form_state->setError(
            $element['input_field'],
            $this->t('Search string must consist of at least one (1) characters')
          );
          //dpm($vals,__FUNCTION__.'::values');
        }
        break;
      }
      case 'ALL':
      case 'IN':
      case 'NOT IN': break;
      case 'BETWEEN': {
        if (!empty($input) && !preg_match('/^\s*\S+\s*\,\s*\S+\s*$/',$input)) {
          $form_state->setError(
            $element['input_field'],
            $this->t(
              'For the %between query, the search string must contain exactly two values divided by a comma (,)',
              array('%between' => $this->getSearchOperators()['BETWEEN'])
            )
          );
          //dpm($vals,__FUNCTION__.'::values');
        }
        break;
      }
      case 'NOT_EMPTY': {
      }
      case 'EMPTY': {
      }
    }
    
  }

  public function buildSearchUrlQuery(FormStateInterface $form_state) {
    
    $vals = $form_state->getValues();
    $keys = array();
    $ops = array();
    if (isset($vals['advanced']) && isset($vals['advanced']['paths']) && !empty($vals['advanced']['paths'])) {
      foreach($vals['advanced']['paths'] as $bundle_id => $paths) {
        $return[$bundle_id]['query_type'] = $paths['query_type']['selection'];
        unset($paths['query_type']);
        foreach ($paths as $path_parameters) {
          if ($path_parameters['input_field'] || trim($path_parameters['operator']) == "NOT_EMPTY" || trim($path_parameters['operator']) == "EMPTY") {
            $ops[] = trim($path_parameters['operator']);
            $keys[] = trim($path_parameters['input_field']);
            $return[$bundle_id]['paths'][] = array($path_parameters['path_selection'],trim($path_parameters['input_field']),$path_parameters['operator']);
          }
        }
      }
    }
    $return['ops'] = $ops;
    $return['bundles'] = array_filter($vals['advanced']['bundles']['select_bundles']);
    if(empty($return['bundles']))
      $return['bundles'] = array_keys($vals['advanced']['bundles']['select_bundles']);
    $return['entity_title'] = $vals['entity_title'];
    // 'keys' must be set for the Search Plugin, don't know why
    $return['keys'] = $vals['entity_title'] ? : implode(', ',$keys);
//    dpm($vals, "ret!");
    return $return;
  }

  public function replaceSelectBoxes(array $form,FormStateInterface $form_state) {
    return $form['advanced']['bundles']['select_bundles'];
  }
  
  public function replacePaths(array $form,FormStateInterface $form_state) {
    
    return $form['advanced']['paths'];
  }
  
  /**
   * Function to see when something is valid to search.
   * std is return !empty($this->keywords);
   */
  public function isSearchExecutable() {
#    dpm($this->searchParameters['ops'], "hallo!");
    // if any of these is NOT EMPTY we can do the search.
    if(isset($this->searchParameters['ops']))
      foreach($this->searchParameters['ops'] as $op) {
#        dpm($op, "op");
        if($op == "NOT_EMPTY" || $op == "EMPTY")
          return TRUE;
      }
    return parent::isSearchExecutable();
  }
  
}
