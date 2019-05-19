<?php

/**
 * @file
 * Contains Drupal\wisski_adapter_zotero\Plugin\wisski_salz\Engine\ZoteroEngine.
 */

namespace Drupal\wisski_adapter_zotero\Plugin\wisski_salz\Engine;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\wisski_adapter_zotero\Query\Query;
use Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity; 
use Drupal\wisski_pathbuilder\Entity\WisskiPathEntity; 
use Drupal\wisski_pathbuilder\PathbuilderEngineInterface;
use Drupal\wisski_salz\NonWritableEngineBase;
use Drupal\wisski_salz\AdapterHelper;
use DOMDocument;
use EasyRdf_Graph;
use EasyRdf_Namespace;
use EasyRdf_Literal;

/**
 * Wiki implementation of an external entity storage client.
 *
 * @Engine(
 *   id = "zotero",
 *   name = @Translation("Zotero"),
 *   description = @Translation("Provides access to Zotero")
 * )
 */
class ZoteroEngine extends NonWritableEngineBase implements PathbuilderEngineInterface {

  protected $uriPattern  = "!^https://www.zotero.org/(.+)s/(.+)/items/itemKey/(.+)$!u";
  
  /**
   * Workaround for super-annoying easyrdf buggy behavior:
   * it will only work on prefixed properties
   */
  protected $rdfNamespaces = array(
    'gnd' => 'http://d-nb.info/standards/elementset/gnd#',
    'geo' => 'http://www.opengis.net/ont/geosparql#',
    'sf' => 'http://www.opengis.net/ont/sf#',    
  );

  protected $all_items = 'itemFields';
  protected $version = '3';
  
  protected $possibleSteps = array();

  protected $server;
  protected $api_key;
  protected $user_group;
  protected $is_user_or_group;

  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'server' => "https://api.zotero.org",
      'api_key' => "",
      'user_group' => '',
      'is_user_or_group' => 'user',
    ];
  }

  public function loadSteps() {
    $url = $this->server . '/' . $this->all_items . '?v=' . $this->version . '&key=' . $this->api_key;
    ini_set("allow_url_fopen", 1);
    
    $json = file_get_contents($url);
    
#    dpm(serialize($http_response_header), "header");
    
    $objs = json_decode($json);
    
    $steps = array();
    
#    dpm($objs, "obj");
    
    foreach($objs as $obj) {
      if(isset($obj->field)) {
        $steps['Literature'][$obj->field] = NULL;
        
#        if($obj->field == "creators") {
#          $steps['Literature'][$obj->field . ' creatorType'] = NULL;
#          $steps['Literature'][$obj->field . ' firstName'] = NULL;
#          $steps['Literature'][$obj->field . ' lastName'] = NULL;
#        }
        
      }
    }
    
    $steps['Literature']['creators'] = NULL;
    $steps['Literature']['directLink'] = NULL;
    $steps['Literature']['itemType'] = NULL;
    
    $this->possibleSteps = $steps;
#    dpm($this->possible_steps);
  }
  
  public function loadAllItems($count = FALSE, $limit = 25, $offset = 0, $where = "", $sort = "") {
    $url = $this->server . '/' . $this->is_user_or_group . 's/' . $this->user_group . '/items/top?v=' . $this->version . $where . $sort . '&limit=' . $limit . '&start=' . $offset . '&key=' . $this->api_key;
#    dpm($url);
    ini_set("allow_url_fopen", 1);
    
    $json = file_get_contents($url);
    
    // get the header for count
    $header = $http_response_header;
    
#    dpm($header, "header");
    
    $objs = json_decode($json);
    
    $data = array();
    
    foreach($objs as $obj) {
      $data[$obj->key] = $obj->data;
    }

    if($count) {
      foreach($header as $head) {
#        dpm($head, "head");
        if(strpos($head, "Total-Results: ") !== FALSE) {
          $ret = intval(substr($head, strlen("Total-Results: ")));
#          dpm($ret, "ret!");
          return $ret;
        }
      }
      return 0;
    } else   
      return $data;
  
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {

    // this does not exist
    parent::setConfiguration($configuration);
    $this->server = $this->configuration['server'];
    $this->api_key = $this->configuration['api_key'];
    $this->user_group = $this->configuration['user_group'];
    $this->is_user_or_group = $this->configuration['is_user_or_group'];
  }


  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return array(
      'server' => $this->server,
      'api_key' => $this->api_key,
      'user_group' => $this->user_group,
      'is_user_or_group' => $this->is_user_or_group,
    ) + parent::getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildConfigurationForm($form, $form_state);

    $form['server'] = array(
      '#type' => 'textfield',
      '#title' => 'Base Url of the server',
      '#default_value' => $this->server,
      '#return_value' => $this->server,
    );
    
    $form['api_key'] = array(
      '#type' => 'textfield',
      '#title' => 'The Zotero Api Key - should be generated on the zotero website in your user profile.',
      '#default_value' => $this->api_key,
      '#return_value' => $this->api_key,
    );

    $form['user_group'] = array(
      '#type' => 'textfield',
      '#title' => 'The user/group to access',
      '#default_value' => $this->user_group,
      '#return_value' => $this->user_group,
    );

    $form['is_user_or_group'] = array(
      '#type' => 'radios',
      '#title' => 'Is this a user or a group account?',
      '#options' => array('user' => $this->t('User'), 'group' => $this->t('Group')), 
      '#default_value' => $this->is_user_or_group,
      '#return_value' => $this->is_user_or_group,
    );
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    
    $this->server = $form_state->getValue('server');
    $this->api_key = $form_state->getValue('api_key');
    $this->user_group = $form_state->getValue('user_group');
    $this->is_user_or_group = $form_state->getValue('is_user_or_group');
  }  
  
  
  
  


  /**
   * {@inheritdoc} 
   */
  public function hasEntity($entity_id) {
    // use the new function
    
#    $uris = AdapterHelper::getDrupalIdForUri($entity_id, FALSE, $this->adapterId());
    $uris = AdapterHelper::getUrisForDrupalId($entity_id, $this->adapterId());
#    dpm($uris, "uris");
    if (empty($uris)) return FALSE;
    
    #foreach ($uris as $uri) {
    #  // fetchData also checks if the URI matches the GND URI pattern
    #  // and if so tries to get the data.
      if ($this->fetchData($uris)) {
        return TRUE;
      }
    #}
 
#    if ($this->fetchData($entity_id)) {
#      return TRUE;
#    }
 
    return FALSE;
  }


  public function fetchData($uri, $id = NULL) {
    

    if (!$id) {
      if (!$uri) {
        return FALSE;
      } elseif (preg_match($this->uriPattern, $uri, $matches)) {
        $id = $matches[3];
        $gu_id = $matches[2];
      } else {
        // not a URI
        return FALSE;
      }
    }

    // not our id? 
    if($gu_id != $this->user_group)
      return;

    
#    dpm($id, "yay!");
    
#    return NULL;

#    dpm(serialize($this));
    
    // 
    $cache = \Drupal::cache('wisski_adapter_zotero');
    $data = $cache->get($id);
#    dpm($data, "data from cache");
    if ($data) {
      return $data->data;
    }

    $url = $this->server . '/' . $this->is_user_or_group . 's/' . $this->user_group . '/items/' . $id . '?v=' . $this->version . '&limit=1&start=0&key=' . $this->api_key;
#    dpm($url);

#    dpm(serialize($this);

    ini_set("allow_url_fopen", 1);
    
    $json = file_get_contents($url);
#            
#  $result = array();
#   

    $obj = json_decode($json);
    
    $data = array();
    
#    dpm($obj, "dat");
#    return;
            
    $outarr = array();
    
    foreach($obj->data as $key => $objdata) {
      $data['Literature'][$key] = array($objdata);

      if($key == "creators") {
      
        $data['Literature'][$key] = array();
      
        foreach($objdata as $creator) {
          if(isset($creator->lastName) && isset($creator->firstName)) {
            $data['Literature'][$key][] = $creator->lastName . ', ' . $creator->firstName;
          } else if(isset($creator->lastName)) {
            $data['Literature'][$key][] = $creator->lastName;
          } else if(isset($creator->firstName)) {
            $data['Literature'][$key][] = $creator->firstName;
          } else if(isset($creator->name)) {
            $data['Literature'][$key][] = $creator->name;
          }
        }
      }

    }
    
    $data['Literature']['directLink'][] = $uri;

    $cache->set($id, $data);
#    dpm($data, "data");
    return $data;

  }


  /**
   * {@inheritdoc}
   */
  public function checkUriExists ($uri) {
    return !empty($this->fetchData($uri));
  }


  /**
   * {@inheritdoc} 
   */
  public function createEntity($entity) {
    return;
  }
  
  public function getBundleIdsForEntityId($id) {
    $uri = $this->getUriForDrupalId($id);
    $data = $this->fetchData($uri);
    
    $pbs = $this->getPbsForThis();
    $bundle_ids = array();
    foreach($pbs as $key => $pb) {
      $groups = $pb->getMainGroups();
      foreach ($groups as $group) {
        $path = $group->getPathArray(); 
#dpm(array($path,$group, $pb->getPbPath($group->getID())),'bundlep');
        if (isset($data[$path[0]])) {
          $bid = $pb->getPbPath($group->getID())['bundle'];
#dpm(array($bundle_ids,$bid),'bundlesi');
          $bundle_ids[] = $bid;
        }
      }
    }
    
#dpm($bundle_ids,'bundles');

    return $bundle_ids;

  }


  /**
   * {@inheritdoc} 
   */
  public function loadFieldValues(array $entity_ids = NULL, array $field_ids = NULL, $bundle = NULL,$language = LanguageInterface::LANGCODE_DEFAULT) {
    dpm("load field values!");    
    if (!$entity_ids) {
      // TODO: get all entities
      $entity_ids = array(
        "http://d-nb.info/gnd/11852786X"
      );
    }
    
    $out = array();

    foreach ($entity_ids as $eid) {

      foreach($field_ids as $fkey => $fieldid) {  
        
        $got = $this->loadPropertyValuesForField($fieldid, array(), $entity_ids, $bundleid_in, $language);

        if (empty($out)) {
          $out = $got;
        } else {
          foreach($got as $eid => $value) {
            if(empty($out[$eid])) {
              $out[$eid] = $got[$eid];
            } else {
              $out[$eid] = array_merge($out[$eid], $got[$eid]);
            }
          }
        }

      }
 
    }

    return $out;

  }
  
  
  /**
   * {@inheritdoc} 
   */
  public function loadPropertyValuesForField($field_id, array $property_ids, array $entity_ids = NULL, $bundleid_in = NULL,$language = LanguageInterface::LANGCODE_DEFAULT) {
#dpm(func_get_args(), 'lpvff');

    $main_property = \Drupal\field\Entity\FieldStorageConfig::loadByName('wisski_individual', $field_id);
    if(!empty($main_property)) {
      $main_property = $main_property->getMainPropertyName();
    }
    
#     drupal_set_message("mp: " . serialize($main_property) . "for field " . serialize($field_id));
#    if (in_array($main_property,$property_ids)) {
#      return $this->loadFieldValues($entity_ids,array($field_id),$language);
#    }
#    return array();

    if(!empty($field_id) && empty($bundleid_in)) {
      drupal_set_message("Es wurde $field_id angefragt und bundle ist aber leer.", "error");
      dpm(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
      return;
    }
    

    $pbs = array($this->getPbForThis());
    $paths = array();
    foreach($pbs as $key => $pb) {
      if (!$pb) continue;
      $field = $pb->getPbEntriesForFid($field_id);
#dpm(array($key,$field),'öäü');
      if (is_array($field) && !empty($field['id'])) {
        $paths[] = WisskiPathEntity::load($field["id"]);
      }
    }
      
    $out = array();

    foreach ($entity_ids as $eid) {
      
      if($field_id == "eid") {
        $out[$eid][$field_id] = array($eid);
      } elseif($field_id == "name") {
        // tempo hack
        $out[$eid][$field_id] = array($eid);
        continue;
      } elseif ($field_id == "bundle") {
      
      // Bundle is a special case.
      // If we are asked for a bundle, we first look in the pb cache for the bundle
      // because it could have been set by 
      // measures like navigate or something - so the entity is always displayed in 
      // a correct manor.
      // If this is not set we just select the first bundle that might be appropriate.
      // We select this with the first field that is there. @TODO:
      // There might be a better solution to this.
      // e.g. knowing what bundle was used for this id etc...
      // however this would need more tables with mappings that will be slow in case
      // of a lot of data...
        
        if(!empty($bundleid_in)) {
          $out[$eid]['bundle'] = array($bundleid_in);
          continue;
        } else {
          // if there is none return NULL
          $out[$eid]['bundle'] = NULL;
          continue;
        }
      } else {
        
        if (empty($paths)) {
#          $out[$eid][$field_id] = NULL;              
        } else {
          
          foreach ($paths as $key => $path) {
            $values = $this->pathToReturnValue($path, $pbs[$key], $eid, 0, $main_property);
            if (!empty($values)) {
              foreach ($values as $v) {
                $out[$eid][$field_id][] = $v;
              }
            }
          }
        }
      }
    }
   
#dpm($out, 'lfp');   
    return $out;

  }


  public function pathToReturnValue($path, $pb, $eid = NULL, $position = 0, $main_property = NULL) {
#dpm($path->getName(), 'spam');
    $field_id = $pb->getPbPath($path->getID())["field"];

    $uri = AdapterHelper::getUrisForDrupalId($eid, $this->adapterId());
    $data = $this->fetchData($uri);
#    dpm($data, "data");
    if (!$data) {
      return [];
    }
    $path_array = $path->getPathArray();
    $path_array[] = $path->getDatatypeProperty();
    $data_walk = $data;
#    dpm($data_walk, "data");
#    dpm($path_array, "pa");
    do {
      $step = array_shift($path_array);
      if (isset($data_walk[$step])) {
        $data_walk = $data_walk[$step];
      } else {
        // this is oversimplified in case there is another path in question but this
        // one had no data. E.g. a preferred name exists, but no variant name and 
        // the variant name is questioned. Then it will resolve most of the array
        // up to the property and then stop here. 
        //
        // in this case nothing should stay in $data_walk because
        // the foreach below would generate empty data if there is something
        // left.
        // By Mark: I don't know if this really is what should be here, martin
        // @Martin: Pls check :)
        $data_walk = array();
        continue; // go to the next path
      }
    } while (!empty($path_array));
    // now data_walk contains only the values
    $out = array();
#    dpm($data_walk, "walk");
#    return $out;
    foreach ($data_walk as $value) {
      if (empty($main_property)) {
        $out[] = $value;
      } else {
        $out[] = array($main_property => $value);
      }
    }
#    drupal_set_message(serialize($out));
    return $out;

  }


  /**
   * {@inheritdoc} 
   */
  public function getPathAlternatives($history = [], $future = []) {
#    dpm($this->loadSteps(), "loaded!");

    if(empty($this->possibleSteps))
      $this->loadSteps();

    if (empty($history)) {
      $keys = array_keys($this->possibleSteps);
      return array_combine($keys, $keys);
    } else {
      // we don't want to return anything anway.
      return array();
    }
  }
  
  
  /**
   * {@inheritdoc} 
   */
  public function getPrimitiveMapping($step) {
    $keys = array_keys($this->possibleSteps[$step]);
    return array_combine($keys, $keys);
  }
  
  
  /**
   * {@inheritdoc} 
   */
  public function getStepInfo($step, $history = [], $future = []) {
    return array($step, '');
  }


  public function getQueryObject(EntityTypeInterface $entity_type,$condition, array $namespaces) {
    return new Query($entity_type,$condition,$namespaces, $this);
  }

  public function providesDatatypeProperty() {
    return TRUE;
  }

    /**
   * Gets the bundle and loads every individual in the store
   * the fun is - we only can handle objects, so we give them to them.
   *
   */ 
  public function loadIndividualsForBundle($bundleid, $pathbuilder, $limit = NULL, $offset = NULL, $count = FALSE, $conditions = FALSE, $sorts = FALSE) {
#    dpm('limit:' . $limit . 'offset' . $offset . 'count' . serialize($count) . serialize($bundleid) . " " . serialize($pathbuilder), "I am called.");
#    $data = $this->loadAllItems();
#    return;
#    dpm(microtime(), "mic");
#    $con = sqlsrv_connect($this->server, array("Database"=>$this->database, "UID"=>$this->user, "PWD"=>$this->password) );
#    dpm(microtime(), "mic2");

#    dpm($offset, "offset");
#    dpm(serialize($count), "cnt");

#    dpm($conditions, "cond");
    
    // if there is nothing there, do nothing!
    if(empty($pathbuilder->getGroupsForBundle($bundleid))) {
      return array();
    }
    
    $where = "";

    $sort = "";

    if(!empty($sorts)) {
      foreach($sorts as $subsort) {
    
        $pos = strpos($subsort['field'], $pathbuilder->id() . "__");
    
#      dpm($pos, "pos");
#      dpm($sort['field'], "sort");
#      dpm($pathbuilder->id(), "id");
    
        if($pos === FALSE)
          continue;
    
        $pathid = substr($subsort['field'], $pos + strlen($pathbuilder->id() . "__"));
      
        $path = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($pathid);
      
        $dt = $path->getDatatypeProperty();

        if($dt == "creators");
          $dt = "creator";
      
        $sort .= "&sort=" . $dt . "&direction=" . strtolower($subsort['direction']);
#      dpm($sort, "sort");
      // this can only handle one!
        continue;      
#      dpm($path, "path!");
#      
#      dpm($sort['field'], "sort");
#            
#      dpm($pathid, "pid");
#      
#      $pbp = $pathbuilder->getPbPath($pathid);
#      
#      dpm($pbp, "pbp");
      
      }
    }

    
    // build conditions for where
    
    foreach($conditions as $cond) {
      // if it is a bundle condition, skip it...
      if($cond['field'] == "bundle")
        continue;

      /*        
      $pb_and_path = explode(".", $cond['field']);
      
      $pathid = $pb_and_path[1];
      
      if(empty($pathid))
        continue;
            
      $path = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($pathid);
      
      if(empty($path))
        continue;         
      */
      $where .= "&q=";
#      dpm($cond);
    
      // @todo this probably has to be escaped somehow?!
      $where .= urlencode($cond['value']);
      break;
    }
    
    
#    dpm($count, "count");

    if((empty($limit) && empty($offset)) || $count) {
      $data = $this->loadAllItems(TRUE, 1, 0, $where);
 #    dpm($data, "datacnt");
      $arr = array();
 
      for($i = 0; $i < $data; $i++) {
        $arr[$i] = array('eid' => $i, 'bundle' => $i, 'name' => $i);
      }
 
      return $arr;
    } else {
      $data = $this->loadAllItems(FALSE, $limit, $offset, $where, $sort);
      
#      dpm($data, "data");

      $outarr = array();
      
      foreach($data as $dat) {

        $uri = "https://www.zotero.org/" . $this->is_user_or_group . "s/" . $this->user_group . "/items/itemKey/" . $dat->key;
        $uriname = AdapterHelper::getDrupalIdForUri($uri,TRUE,$this->adapterId());
        
        $outarr[$uriname] = array('eid' => $uriname, 'bundle' => $bundleid, 'name' => $uri);
      }
      
#      dpm($outarr, "outarr");
      
      return $outarr;
    }
  }

} 
