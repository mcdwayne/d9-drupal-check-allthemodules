<?php

namespace Drupal\wisski_core;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityStorageBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;

use Drupal\file\Entity\File;
use Drupal\file\FileStorage;
use Drupal\image\Entity\ImageStyle;

use Drupal\wisski_core\Entity\WisskiEntity;
use Drupal\wisski_core\Query\WisskiQueryInterface;
use Drupal\wisski_core\WisskiCacheHelper;
use Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity;
use Drupal\wisski_salz\AdapterHelper;
use Drupal\wisski_salz\Entity\Adapter;



/**
 * Test Storage that returns a Singleton Entity, so we can see what the FieldItemInterface does
 */
class WisskiStorage extends ContentEntityStorageBase implements WisskiStorageInterface {

  /*
  public function create(array $values = array()) {
    $user = \Drupal::currentUser();
    
    
    dpm($values, "before");
    if(!isset($values['uid']))
      $values['uid'] = $user->id();
      
    dpm($values, "values");
    return parent::create($values);
  }
  */
  
  private $pbmanager = NULL;
   
  
  /**
   * stores mappings from entity IDs to arrays of storages, that handle the id
   * and arrays of bundles the entity is in
   */
  private $entity_info = array();


  /**
   * Internal cache - needed since drupal 8.6
   */
  private $stored_entities = NULL;

  //cache the style in this object in case it will be used for multiple entites
  private $image_style;
  private $adapter;
  private $preview_image_adapters = array();

  private $tableMapping = NULL;

  public function getCacheValues($ids, $field_id = array(), $bundle_id = array()) {
  
    foreach($ids as $id) {
      $cached_field_values = db_select('wisski_entity_field_properties','f')
        ->fields('f',array('fid', 'ident','delta','properties'))
        ->condition('eid',$id);
#        ->condition('bid',$values[$id]['bundle'])
#          ->condition('fid',$field_name)

      if(!empty($field_ids)) {
        $cached_field_values = $cached_field_values->condition('fid', $field_id);
      }
        
      if(!empty($bundle_ids)) {
        $cached_field_values = $cached_field_values->condition('bid', $bundle_id);
      }

      $cached_field_values = $cached_field_values->execute()
        ->fetchAll();

      return $cached_field_values;    
    
    }
    
    
  
  }

  public function addCacheValues($ids, $values) {
#    dpm($ids, "ids");
#    dpm($values, "values");
    
    // default initialisation.
    $entities = NULL;
    
    // add the values from the cache
    foreach ($ids as $id) {
      
      //@TODO combine this with getEntityInfo
      if (!empty($values[$id])) {
#ddl($values, 'values');
        if (!isset($values[$id]['bundle'])) continue;

        // load the cache
        $cached_field_values = db_select('wisski_entity_field_properties','f')
          ->fields('f',array('fid', 'ident','delta','properties'))
          ->condition('eid',$id)
          ->condition('bid',$values[$id]['bundle'])
#          ->condition('fid',$field_name)
          ->execute()
          ->fetchAll();
#          ->fetchAllAssoc('fid');
// fetchAllAssoc('fid') is wrong here because
// if you have duplicateable fields it will fail!
#        dpm($cached_field_values, "argh");                          

        $pbs_info = \Drupal::service('wisski_pathbuilder.manager')->getPbsUsingBundle($values[$id]['bundle']);

        foreach($cached_field_values as $key => $cached_field_value) {
          $field_id = $cached_field_value->fid;
          
#          if($field_id == 'b1abe31d92a85c73f932db318068d0d5')
#            drupal_set_message(serialize($cached_field_value));
#          dpm($cached_field_value->properties, "sdasdf");
#          dpm($values[$id][$field_id], "is set to");
#          dpm(serialize(isset($values[$id][$field_id])), "magic");
          
          // empty here might make problems
          // if we loaded something from TS we can skip the cache.
          // By Mark: Unfortunatelly this is not true. There is a rare case
          // that there is additional information, e.g. in files.
          if( isset($values[$id][$field_id]) ) {
            $cached_value = unserialize($cached_field_value->properties);
            $delta = $cached_field_value->delta;

            // if we really have information, merge that!
            if(isset($values[$id][$field_id][$delta]) && is_array($values[$id][$field_id][$delta]) && !empty($cached_value))
              $values[$id][$field_id][$delta] = array_merge($cached_value, $values[$id][$field_id][$delta]); #, $cached_value);

            continue;
          }
            
          // if we didn't load something, we might need the cache.
          // however not if the TS is the normative thing and has no data for this.
#          $pbs_info = \Drupal::service('wisski_pathbuilder.manager')->getPbsUsingBundle($values[$id]['bundle']);
#          dpm($pbs_info);
          
          $continue = FALSE;
          // iterate through all infos
          foreach($pbs_info as $pb_info) {
            
            // lazy-load the pb
            if(empty($pb_cache[$pb_info['pb_id']]))
              $pb_cache[$pb_info['pb_id']] = WisskiPathbuilderEntity::load($pb_info['pb_id']);
            $pb = $pb_cache[$pb_info['pb_id']];
                        
            if(!empty($pb->getPbEntriesForFid($field_id))) {
#              drupal_set_message("I found something for $field_id");
              // if we have a field in any pathbuilder matching this
              // we continue.
              $continue = TRUE;
              break;
            }
          }
          
          // do it
          if($continue)
            continue;
          
                  
#          dpm($cached_field_value->properties, "I am alive!");

          $cached_value = unserialize($cached_field_value->properties);
          
          if(empty($cached_value))
            continue;

          // now it should be save to set this value
#          if(!empty($values[$id][$field_id]))
#            $values[$id][$field_id] = 
#          else
#          dpm($cached_value, "loaded from cache.");
          $values[$id][$field_id] = $cached_value;
        }
        
#        dpm($values, "values after");
             
        try {
#        dpm("yay!");
#          dpm(serialize($values[$id]));
          $entity = $this->create($values[$id]);
#          dpm(serialize($entity), "yay!");
          $entity->enforceIsNew(FALSE);
          $entities[$id] = $entity;
#          dpm($entities);
        } catch (\Exception $e) {
          drupal_set_message("An error occured: " . $e->getMessage(), "error");
        }
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {
#    dpm("yay, I do loadmultiple!");
#   dpm(microtime(), "first!");
  //dpm($ids,__METHOD__);
    $entities = array();

    // this loads everything from the triplestore
    $values = $this->getEntityInfo($ids);
#    dpm($values, "values");
#    dpm(microtime(), "after load");
    $pb_cache = array();
    
    $moduleHandler = \Drupal::service('module_handler');
    if (!$moduleHandler->moduleExists('wisski_pathbuilder')){
      return NULL;
    }
                      
/*
    // add the values from the cache
    foreach ($ids as $id) {
      
      //@TODO combine this with getEntityInfo
      if (!empty($values[$id])) {
#ddl($values, 'values');
        if (!isset($values[$id]['bundle'])) continue;

        // load the cache
        $cached_field_values = db_select('wisski_entity_field_properties','f')
          ->fields('f',array('fid', 'ident','delta','properties'))
          ->condition('eid',$id)
          ->condition('bid',$values[$id]['bundle'])
#          ->condition('fid',$field_name)
          ->execute()
          ->fetchAll();
#          ->fetchAllAssoc('fid');
// fetchAllAssoc('fid') is wrong here because
// if you have duplicateable fields it will fail!
#        dpm($cached_field_values, "argh");                          

        $pbs_info = \Drupal::service('wisski_pathbuilder.manager')->getPbsUsingBundle($values[$id]['bundle']);

        foreach($cached_field_values as $key => $cached_field_value) {
          $field_id = $cached_field_value->fid;
          
#          if($field_id == 'b1abe31d92a85c73f932db318068d0d5')
#            drupal_set_message(serialize($cached_field_value));
#          dpm($cached_field_value->properties, "sdasdf");
#          dpm($values[$id][$field_id], "is set to");
#          dpm(serialize(isset($values[$id][$field_id])), "magic");
          
          // empty here might make problems
          // if we loaded something from TS we can skip the cache.
          // By Mark: Unfortunatelly this is not true. There is a rare case
          // that there is additional information, e.g. in files.
          if( isset($values[$id][$field_id]) ) {
            $cached_value = unserialize($cached_field_value->properties);
            $delta = $cached_field_value->delta;

            // if we really have information, merge that!
            if(isset($values[$id][$field_id][$delta]) && is_array($values[$id][$field_id][$delta]) && !empty($cached_value))
              $values[$id][$field_id][$delta] = array_merge($cached_value, $values[$id][$field_id][$delta]); #, $cached_value);

            continue;
          }
            
          // if we didn't load something, we might need the cache.
          // however not if the TS is the normative thing and has no data for this.
#          $pbs_info = \Drupal::service('wisski_pathbuilder.manager')->getPbsUsingBundle($values[$id]['bundle']);
#          dpm($pbs_info);
          
          $continue = FALSE;
          // iterate through all infos
          foreach($pbs_info as $pb_info) {
            
            // lazy-load the pb
            if(empty($pb_cache[$pb_info['pb_id']]))
              $pb_cache[$pb_info['pb_id']] = WisskiPathbuilderEntity::load($pb_info['pb_id']);
            $pb = $pb_cache[$pb_info['pb_id']];
                        
            if(!empty($pb->getPbEntriesForFid($field_id))) {
#              drupal_set_message("I found something for $field_id");
              // if we have a field in any pathbuilder matching this
              // we continue.
              $continue = TRUE;
              break;
            }
          }
          
          // do it
          if($continue)
            continue;
          
                  
#          dpm($cached_field_value->properties, "I am alive!");

          $cached_value = unserialize($cached_field_value->properties);
          
          if(empty($cached_value))
            continue;

          // now it should be save to set this value
#          if(!empty($values[$id][$field_id]))
#            $values[$id][$field_id] = 
#          else
#          dpm($cached_value, "loaded from cache.");
          $values[$id][$field_id] = $cached_value;
        }
        
#        dpm($values, "values after");
             
        try {
#        dpm("yay!");
#          dpm(serialize($values[$id]));
          $entity = $this->create($values[$id]);
#          dpm(serialize($entity), "yay!");
          $entity->enforceIsNew(FALSE);
          $entities[$id] = $entity;
#          dpm($entities);
        } catch (\Exception $e) {
          drupal_set_message("An error occured: " . $e->getMessage(), "error");
        }
      }
    }
*/
    $entities = $this->addCacheValues($ids, $values);    
#    dpm(microtime(), "last!");
#    dpm(array('in'=>$ids,'out'=>$entities),__METHOD__);
    return $entities;
  }

  /**
   * gathers entity field info from all available adapters
   * @param $id entity ID
   * @param $cached TRUE for static caching, FALSE for forced update
   * @return array keyed by entity id containing entity field info
   */
  protected function getEntityInfo(array $ids,$cached = FALSE) {
#    drupal_set_message(serialize($ids) .  " : " .  serialize($this));
#    dpm(microtime(), "in1 asking for " .  serialize($ids));
#    dpm($this->latestRevisionIds, "yay123!");
    // get the main entity id
    // if this is NULL then we have a main-form
    // if it is not NULL we have a sub-form    
    if(!empty($this->entities)) 
      $mainentityid = key($this->entities);
    else if(!empty($this->stored_entities))
      $mainentityid = key($this->stored_entities);
    else
      $mainentityid = NULL;

  

#    dpm($mainentityid);

#    drupal_set_message("key is: " . serialize($mainentityid));

    // this is an array of the known entities.
    // whenever some adapter knows any of the entities that
    // are queried here, it sets the corresponding id
    // with $id => TRUE
    $known_entity_ids = array();

    $entity_info = &$this->entity_info;
    if ($cached) {
      $ids = array_diff_key($ids,$entity_info);
      if (empty($ids)) return $entity_info;
      // if there remain entities that were not in cache, $ids now only 
      // contains their ids and we load these in the remaining procedure.
    }
    
    // prepare config variables if we only want to use main bundles.    
    $topBundles = array();
    $set = \Drupal::configFactory()->getEditable('wisski_core.settings');
    $only_use_topbundles = $set->get('wisski_use_only_main_bundles');
#dpm(microtime(), "in2");
    if($only_use_topbundles) 
      $topBundles = \Drupal\wisski_core\WisskiHelper::getTopBundleIds();

    $bundle_from_uri = \Drupal::request()->query->get('wisski_bundle');

    $adapters = entity_load_multiple('wisski_salz_adapter');
    $info = array();
    // for every id
    foreach($ids as $id) {
    
      $this->stored_entities[$id] = $id;
    
#      dpm($mainentityid, "main");
#      dpm($id, "id");
#      dpm(microtime(), "in3");
      //make sure the entity knows its ID at least
      $info[$id]['eid'] = $id;
      
      //see if we got bundle information cached. Useful for entity reference and more      
      $overall_bundle_ids = array();

      // by mark:
      // in case of a main form always use the uri-parameter if provided
      // this is always better than trying something else!
      if( empty($mainentityid) && !empty($bundle_from_uri) ) {
#        $cached_bundle = WisskiCacheHelper::getCallingBundle($id);
#        dpm($id . " " . serialize($bundle_ids) . " and " . serialize($cached_bundle));
        $cached_bundle = $bundle_from_uri;
        
        $this->writeToCache($id, $cached_bundle);
      } else {
        $cached_bundle = WisskiCacheHelper::getCallingBundle($id);
#      drupal_set_message($id . " " . serialize($bundle_ids) . " and " . serialize($cached_bundle));      
        // only use that if it is a top bundle when the checkbox was set. Always use it otherwise.
        if ($cached_bundle) {
          if($only_use_topbundles && empty($mainentityid) && !in_array($cached_bundle, $topBundles)) {
          
            // check if there is any valid top bundle.
            $valid_topbundle = AdapterHelper::getBundleIdsForEntityId($id, TRUE);
            
            // if we found any, we trust the system that this is probably the best!
            if($valid_topbundle)
              $cached_bundle = current($valid_topbundle); // whichever system might have more than one of this? I dont know...

            // if we did not find any top bundle we guess that the cached one
            // will probably be the best. We dont start searching
            // for anything again....
            
            //$cached_bundle = NULL;
          } else
            $info[$id]['bundle'] = $cached_bundle;
        #dpm($cached_bundle, "cb");
        }
      }
            
#      drupal_set_message(serialize($bundle_ids) . " and " . serialize($cached_bundle));
#      dpm(microtime(), "in4");
      // ask all adapters
      foreach($adapters as $aid => $adapter) {
        // if they know that id
#        drupal_set_message(serialize($adapter->hasEntity($id)) . " said adapter " . serialize($adapter));
        if($adapter->hasEntity($id)) {
          $known_entity_ids[$id] = TRUE;
          
          // if we have something in cache, take that first.
          if (isset($cached_bundle)) {
            $bundle_ids = array($cached_bundle);
          } else {
          
            // take all bundles
            $bundle_ids = $adapter->getBundleIdsForEntityId($id);
  
            if (empty($bundle_ids)) {
              // if the adapter cannot determine at least one bundle, it will
              // also not be able to contribute to the field data
              // TODO: check if this assumption is right!
              continue; // next adapter
            }

            if (!empty($bundle_from_uri) && (empty($bundle_ids) || in_array($bundle_from_uri, $bundle_ids))) {
              $bundle_ids = array($bundle_from_uri);
            }

/*          By Martin: the following lines have to be replaced by the ones above.
            the code below would give priority to the uri bundle for entities 
            in subforms, too.
            with the code above it is no longer possible to brute force a 
            certain bundle, however.
            
            By Mark: Works - also with the transdisciplinary approach
            // if the bundle is given via the uri, we use that and only that
            if(!empty($bundle_from_uri))
              $bundle_ids = array($bundle_from_uri);
            else {
              // if so - ask for the bundles for that id
              // we assume bundles to be prioritized i.e. the first bundle in the set is the best guess for the view
#              drupal_set_message(serialize($bundle_ids));
            }*/
          }
#          dpm($bundle_ids, "bids");
#          dpm($overall_bundle_ids, "obids");
          $overall_bundle_ids = array_merge($overall_bundle_ids, $bundle_ids);

          $bundle_ids = array_slice($bundle_ids,0,1); // HACK! (randomly takes the first one
          #drupal_set_message(serialize($bundle_ids) . " and " . serialize($cached_bundle) . " for " . serialize($ids));
          foreach($bundle_ids as $bundleid) {
            // be more robust.
            if(empty($bundleid)) {
              drupal_set_message("Beware, there is somewhere an empty bundle id specified in your pathbuilder!", "warning");
              drupal_set_message("I have been looking for a bundle for $id and I got from cache: " . serialize($cached_bundle) . " and I have left: " . serialize($bundle_ids));
              continue;
            }
            
            // do this here because if we only use main bundles we need to store this for the title
            if($cached_bundle != $bundleid);
              $this->writeToCache($id, $bundleid);
#            dpm($bundleid, "bid1");
            $field_definitions = $this->entityManager->getFieldDefinitions('wisski_individual',$bundleid);
            #dpm($field_definitions, "yay");
#            wpm($field_definitions, 'gei-fd');
            
#            // see if a field is going to show up.
#            $view_ids = \Drupal::entityQuery('entity_view_display')
#              ->condition('id', 'wisski_individual.' . $bundleid . '.', 'STARTS_WITH')
#              ->execute();
              
#            // is there a view display for it?
#            $entity_view_displays = \Drupal::entityManager()->getStorage('entity_view_display')->loadMultiple($view_ids);
            
#            // did we get something?
#            if(!empty($entity_view_displays))
#              $entity_view_display = current($entity_view_displays);
#            else
#              $entity_view_display = NULL;
#            dpm($entity_view_displays->getComponent('field_name'), "miau");

            try {
#              dpm(microtime(), "load field $field_name");
              foreach ($field_definitions as $field_name => $field_def) {
#                dpm($entity_view_display->getComponent($field_name), "miau");
#                if($field_name 
              
#                dpm(microtime(), "loading $field_name");
              
                $main_property = $field_def->getFieldStorageDefinition()->getMainPropertyName();
#dpm(array($adapter->id(), $field_name,$id, $bundleid),'ge1','error');
                
                if ($field_def instanceof BaseFieldDefinition) {
                  //the bundle key will be set via the loop variable $bundleid
                  if ($field_name === 'bundle') continue;
#                  drupal_set_message("Hello i am a base field ".$field_name);

                  $new_field_values = array();
                  // special case for entity name - call the title generator!
                  if ($field_name === 'name') $new_field_values[$id][$field_name] = array(wisski_core_generate_title($id));

                  //this is a base field and cannot have multiple values
                  //@TODO make sure, we load the RIGHT value
                  if(empty($new_field_values)) 
                    $new_field_values = $adapter->loadPropertyValuesForField($field_name,array(),array($id),$bundleid);

                  if (empty($new_field_values)) continue;
#                  drupal_set_message("Hello i am still alive ". serialize($new_field_values));
                  $new_field_values = $new_field_values[$id][$field_name];
#                  drupal_set_message(serialize($info[$id][$field_name]) . " " . $field_name);
                  if (isset($info[$id][$field_name])) {
                    $old_field_value = $info[$id][$field_name];
                    if (in_array($old_field_value,$new_field_values) && count($new_field_values) > 1) {
#                      drupal_set_message("muahah!2" . $field_name);
                      //@TODO drupal_set_message('Multiple values for base field '.$field_name,'error');
                      //FALLLBACK: do nothing, old field value stays the same
                      //WATCH OUT: if you change this remember to handle preview_image case correctly
                    } elseif (count($new_field_values) === 1) {
#                       drupal_set_message("muahah!1" . $field_name);
                      $info[$id][$field_name] = $new_field_values[0];
                    } else {
#                      drupal_set_message("muahah!" . $field_name);
                      //@TODO drupal_set_message('Multiple values for base field '.$field_name,'error');
                      //WATCH OUT: if you change this remember to handle preview_image case correctly
                    }
                  } elseif (!empty($new_field_values)) {
#                    dpm($new_field_values, "argh: ");
                    $info[$id][$field_name] = current($new_field_values);
#                    $info[$id][$field_name] = $new_field_values;
                  }
                  
#                  dpm($info[$id][$field_name], $field_name);
                  if (!isset($info[$id]['bundle'])) $info[$id]['bundle'] = $bundleid;
                  continue;                 
                }
#                dpm(microtime(), "actual load for field " . $field_name . " in bundle " . $bundleid . " for id " . $id);

#                // check if the field is in the display
#                if(!empty($entity_view_display) && !$entity_view_display->getComponent($field_name))
#                  continue;
                  
                //here we have a "normal field" so we can assume an array of field values is OK
                $new_field_values = $adapter->loadPropertyValuesForField($field_name,array(),array($id),$bundleid);

#                dpm(microtime(), "after load" . serialize($new_field_values));
                if (empty($new_field_values)) continue;
                $info[$id]['bundle'] = $bundleid;

                if ($field_def->getType() === 'entity_reference') {
                  $field_settings = $field_def->getSettings();
#if (!isset($field_settings['handler_settings']['target_bundles'])) dpm($field_def);
                  $target_bundles = $field_settings['handler_settings']['target_bundles'];
                  if (count($target_bundles) === 1) {
                    $target_bundle_id = current($target_bundles);
                  } else if( count($target_bundles) === 1) {
                    drupal_set_message($this->t('There is no target bundle id for field %field - I could not continue.',array('%field' => $field_name)));
                  } else {
                    drupal_set_message($this->t('Multiple target bundles for field %field, %field_label',array('%field' => $field_def->getLabel(), '%field_label' => $field_name)));
#                    dpm($target_bundles);
                    //@TODO create a MASTER BUNDLE and choose that one here
                    $target_bundle_id = current($target_bundles);
                  }
#                  dpm($target_bundles);
                  $target_ids = $new_field_values[$id][$field_name];
                  if (!is_array($target_ids)) $target_ids = array(array('target_id'=>$target_ids));
                  foreach ($target_ids as $target_id) {
#dpm($target_id, "bwtb:$aid");                    
                    $target_id = $target_id['target_id'];
                    $this->writeToCache($target_id,$target_bundle_id);
                    #dpm($info, $field_name);
                    #dpm($target_id, "wrote to cache");
                    #dpm($target_bundle_id, "wrote to cache2");
                  }
                  
                }
                
                // NOTE: this is a dirty hack that sets the text format for all long texts
                // with summary
                // TODO: make format storable and provide option for default format in case
                // no format can be retrieved from storage
                //
                // By Mark: We need this in case we have old data that never was
                // saved before.
                // in this case we take the default format, which is the first one.
                //
                $hack_type = $field_def->getType();
#                dpm(\Drupal::entityManager()->getStorage('filter_format')->loadByProperties(array('status' => TRUE)), "ht");
                if ($hack_type == 'text_with_summary' || $hack_type == 'text_long') {
                  $formats = \Drupal::entityManager()->getStorage('filter_format')->loadByProperties(array('status' => TRUE));
                  $format = current($formats);
#                  dpm($format->get("format"), "format");
                  foreach($new_field_values as &$xid) {
                    foreach($xid as &$xfieldname) {
                      foreach ($xfieldname as &$xindex) {
                        $xindex['format'] = $format->get("format");
                      }
                    }
                  }
#                 $value['value'] = $value;
#                 $value['format'] = 'full_html';
                }

                // we integrate a file handling mechanism that must necessarily
                // also handle other file based fields e.g. "image"
                //
                // the is_file test is a hack. there doesn't seem to be an easy
                // way to determine if a field is file-based. this test tests
                // whether the field depends on the file module. NOTE: there
                // may be other reasons why a field depends on file than
                // handling files
                $is_file = in_array('file',$field_def->getFieldStorageDefinition()->getDependencies()['module']);
                $has_values = !empty($new_field_values[$id][$field_name]);
                if ($is_file && $has_values) {

#                  dpm($new_field_values[$id][$field_name], "yay!");
                  
                  foreach ($new_field_values[$id][$field_name] as $key => &$properties_array) {
                    // we assume that $value is an image URI which is to be
                    // replaced by a File entity ID
                    // we use the special property original_target_id as the
                    // loadPropertyValuesForField()/pathToReturnValues()
                    // replaces the URI with the corresp. file entity id.
                    if (!isset($properties_array['original_target_id']) && !isset($properties_array['target_id'])) continue;
                    else if(isset($properties_array['target_id']) && !isset($properties_array['original_target_id'])) $properties_array['original_target_id'] = $properties_array['target_id'];
                    $file_uri = $properties_array['original_target_id'];
#                    dpm($file_uri, "got");                    
                    $local_uri = '';
                    $properties_array = array(
                      'target_id' => $this->getFileId($file_uri,$local_uri, $id),
                      //this is a fallback
                      //@TODO get the alternative text from the stores
#                      'alt' => substr($local_uri,strrpos($local_uri,'/') + 1),
                    );
#                    dpm($local_uri, "uri");
                  }
                }

                //try finding the weights and sort the values accordingly
                if (isset($new_field_values[$id][$field_name])) {
                  $cached_field_values = db_select('wisski_entity_field_properties','f')
                    ->fields('f',array('ident','delta','properties'))
                    ->condition('eid',$id)
                    ->condition('bid',$bundleid)
                    ->condition('fid',$field_name)
                    ->execute()
                    ->fetchAllAssoc('delta');
                    // this is evil because same values will be killed then... we go for weight instead.
#                    ->fetchAllAssoc('ident');
#                  dpm($cached_field_values, "cfv");
                  if (!empty($cached_field_values)) {
                    $head = array();
                    $tail = array();

                    // there is no delta as a key in this array :( 
                    foreach ($new_field_values[$id][$field_name] as $nfv) {
                      // this would be smarter, however currently the storage can't
                      // store the disamb so this is pointless...
                      //$ident = isset($nfv['wisskiDisamb']) ? $nfv['wisskiDisamb'] : $nfv[$main_property];
                      
                      // this was a good approach, however it is not correct when you have
                      // the same value several times
                      //$ident = $nfv[$main_property];
                      //if (isset($cached_field_values[$ident])) {
                      
                      // store the found item
                      $found_cached_field_value = NULL;
                      
                      // iterate through the cached values and delete
                      // anything we find from the cache to correct the weight
                      foreach($cached_field_values as $key => $cached_field_value) {
#                        dpm($nfv[$main_property], "mp");
                        if((string)$cached_field_value->ident === (string)$nfv[$main_property]) {
                          unset($cached_field_values[$key]);
                          $found_cached_field_value = $cached_field_value;
                          break;
                        }
                      }
                      
                      // if we found something go for it...
                      if (isset($found_cached_field_value)) {
                        $head[$found_cached_field_value->delta] = $nfv + unserialize($found_cached_field_value->properties);
                      } else $tail[] = $nfv;
                    }
                    
                    // do a ksort, because array_merge will resort anyway!
                    ksort($head);
                    ksort($tail);                    

#                    dpm($head, "head");
#                    dpm($tail, "tail");
                    $new_field_values[$id][$field_name] = array_merge($head,$tail);
#                    dpm($new_field_values[$id][$field_name], "miaz");
                  }
                  if (!isset($info[$id]) || !isset($info[$id][$field_name])) $info[$id][$field_name] = $new_field_values[$id][$field_name];
                  else $info[$id][$field_name] = array_merge($info[$id][$field_name],$new_field_values[$id][$field_name]);
                }
              }
            } catch (\Exception $e) {
              drupal_set_message('Could not load entities in adapter '.$adapter->id() . ' because ' . $e->getMessage());
              //throw $e;
            }              
          }     
          
        } else {
#          drupal_set_message("No, I don't know " . $id . " and I am " . $aid . ".");
        }
          
      } // end foreach adapter
      
      if(empty($known_entity_ids[$id])) {
        unset($info[$id]);
        continue;
      }
      
      if (!isset($info[$id]['bundle'])) {
        // we got no bundle information
        // this may especially be the case if we have an instance with no fields filled out.
        // if some adapters found some bundle info, we make a best guess
        if (!empty($overall_bundle_ids)) {
          $top_bundle_ids = \Drupal\wisski_core\WisskiHelper::getTopBundleIds();
          $best_guess = array_intersect($overall_bundle_ids, $top_bundle_ids);
          if (empty($best_guess)) {
            $best_guess = $overall_bundle_ids;
          }
          // if there are multiples, tkae the first one
          // TODO: rank remaining bundles
          $info[$id]['bundle'] = current($best_guess);
        }
      }
      
    }

    $entity_info = WisskiHelper::array_merge_nonempty($entity_info,$info);
#    dpm(microtime(), "out5");
#    dpm($entity_info, 'gei');
    return $entity_info;
  }

  public function getFileId($file_uri,&$local_file_uri='', $entity_id = 0) {
    $value = NULL;
    
#    drupal_set_message('Image uri: '.$file_uri);
    if (empty($file_uri)) return NULL;
    //first try the cache
    $cid = 'wisski_file_uri2id_'.md5($file_uri);
#    dpm(microtime(), "in fid");
    if ($cache = \Drupal::cache()->get($cid)) {
      // check if it really exists.
#      dpm(microtime(), "got fid");
      if(file_exists($file_uri) && filesize($file_uri) > 0) {
        list($file_uri,$local_file_uri) = $cache->data;
        return $file_uri;
      }
    }
    
#    dpm(microtime(), "out");
#   dpm("yay!");       
    // another hack, make sure we have a good local name
    // @TODO do not use md5 since we cannot assume that to be consistent over time
    $local_file_uri = $this->ensureSchemedPublicFileUri($file_uri);
    #dpm($file_uri, "1");
    #dpm($local_file_uri);
    // we now check for an existing 'file managed' with that uri

    // Mark: I don't think that this ever can be fulfilled. I think 
    // most of the time only local_file_uri can be guessed.
    // For sureness: old code below!
    //$query = \Drupal::entityQuery('file')->condition('uri',$file_uri);

    $query = \Drupal::entityQuery('file')->condition('uri',$local_file_uri)->range(0,1);        

    $file_ids = $query->execute();
    if (!empty($file_ids)) {
#      dpm(microtime(), "out fid");
#      dpm($file_ids, "2");
      // if there is one, we must set the field value to the image's FID
      if(file_exists($local_file_uri) && filesize($local_file_uri) > 0) {
        $value = current($file_ids);
      } else {
        file_delete(current($file_ids));
        $file_ids = NULL;
      }
    }         
    
    if(empty($file_ids)) {
#     dpm($local_file_uri, "loc");
      $file = NULL;
      // if we have no managed file with that uri, we try to generate one.
      // in the if test we test whether there exists on the server a file 
      // called $local_file_uri: file_destination() with 2nd param returns
      // FALSE if there is such a file!
      if (file_destination($local_file_uri,FILE_EXISTS_ERROR) === FALSE) {
#            dpm($local_file_uri, "7");
        $file = File::create([
          'uri' => $local_file_uri,
          'uid' => \Drupal::currentUser()->id(),
          'status' => FILE_STATUS_PERMANENT,
        ]);

        $file->setFileName(drupal_basename($local_file_uri));
        $mime_type = \Drupal::service('file.mime_type.guesser')->guess($local_file_uri);

        $file->setMimeType($mime_type);

        $file->save();
        $value = $file->id();
            
      } else {
        try {
      
          // we have to encode the image url, 
          // see http://php.net/manual/en/function.file-get-contents.php
          // NOTE: although the docs say we must use urlencode(), the docs
          // for urlencode() and rawurlencode() specify that rawurlencode
          // must be used for url path part.
          // TODO: this encode hack only works properly if the file name 
          // is the last part of the URL and if only the filename contains
          // disallowed chars. 
          $tmp = explode("/", $file_uri);
#              $tmp[count($tmp) - 1] = rawurlencode($tmp[count($tmp) - 1]);
          $file_uri = join('/', $tmp);

          // replace space.
          // we need to replace space to %20
          // because urls are like http://projektdb.gnm.de/provenienz2014/sites/default/files/Z 2156.jpg
          $file_uri = str_replace(' ', '%20', $file_uri); 

          $data = @file_get_contents($file_uri, false, $context);
          
          if (empty($data)) { 
            drupal_set_message($this->t('Could not fetch file with uri %uri.',array('%uri'=>$file_uri,)),'error');
          }

#              dpm(array('data'=>$data,'uri'=>$file_uri,'local'=>$local_file_uri),'Trying to save image');
          $file = file_save_data($data, $local_file_uri);

          if ($file) {
            $value = $file->id();
            //dpm('replaced '.$file_uri.' with new file '.$value);
          } else {
            drupal_set_message('Error saving file','error');
            //dpm($data,$file_uri);
          }
        }
        catch (EntityStorageException $e) {
          drupal_set_message($this->t('Could not create file with uri %uri. Exception Message: %message',array('%uri'=>$file_uri,'%message'=>$e->getMessage())),'error');
        }
      }

      if (!empty($file)) {
        // we have to register the usage of this file entity otherwise 
        // Drupal will complain that it can't refer to this file when 
        // saving the WissKI individual
        // (it is unclear to me why Drupal bothers about that...)
        \Drupal::service('file.usage')->add($file, 'wisski_core', 'wisski_individual', $entity_id);
      }
    }
    
    
    

#    dpm($value,'image fid');
#    dpm($local_file_uri, "loc");
    //set cache
    \Drupal::cache()->set($cid,array($value,$local_file_uri));
    return $value;
  }

  /**
   * returns a file URI starting with public://
   * if the input URI already looks like this we return unchanged, a full file path
   + to the file directory will be renamed accordingly
   * every other uri will be renamed by a hash function
   */
  public function ensureSchemedPublicFileUri($file_uri) {
    if (strpos($file_uri,'public:/') === 0) return $file_uri;

#    dpm($file_uri, "fi");
#    dpm(\Drupal::service('stream_wrapper.public')->baseUrl(), "fo");

    if (strpos($file_uri,\Drupal::service('stream_wrapper.public')->baseUrl()) === 0) {
      return $this->getSchemedUriFromPublicUri($file_uri);
    }

    $original_path = file_default_scheme() . '://wisski_original/';

    file_prepare_directory($original_path, FILE_CREATE_DIRECTORY);

    // do a htmlentities in case of any & or fragments...
    $extension = htmlentities(substr($file_uri,strrpos($file_uri,'.')));
    
    // load the valid image extensions
    $image_factory = \Drupal::service('image.factory'); 
    $supported_extensions = $image_factory->getSupportedExtensions();

    $extout = "";
#    dpm($supported_extensions);
    
    // go through them and see if there is any in this extension
    // fragment. If so - make it "clean" and get rid of any 
    // appended fragment parts.
    foreach($supported_extensions as $key => $ext) {
      if(strpos($extension, $ext)) {
        $extout = '.' . $ext;
        break;
      }
    }
    
    // if not - we assume jpg.
    if((empty($extout) && empty($extension)) || strpos($extension, "php") !== FALSE )
      $extout = '.jpg';
    else if(!empty($extension)) // keep extensions if there are any - for .skp like in the kuro-case.
      $extout = $extension;
    
#    dpm($extension, "found ext");

    // this is evil in case it is not .tif or .jpeg but something with . in the name...
#    return file_default_scheme().'://'.md5($file_uri).substr($file_uri,strrpos($file_uri,'.'));    
    // this is also evil, because many modules can't handle public:// :/
    // to make it work we added a directory.
    return file_default_scheme().'://wisski_original/'.md5($file_uri).$extout;
    // external uri doesn't work either
    // this is just a documentation of what I've tried...
#    return \Drupal::service('stream_wrapper.public')->baseUrl() . '/' . md5($file_uri);
#    return \Drupal::service('file_system')->realpath( file_default_scheme().'://'.md5($file_uri) );
#    return \Drupal::service('stream_wrapper.public')->getExternalUrl() . '/' . md5($file_uri);
#    return str_replace('/foko2014/', '', file_url_transform_relative(file_create_url(file_default_scheme().'://'.md5($file_uri))));

  }
  
  public function getPublicUrlFromFileId($file_id) {
    
    if ($file_object = File::load($file_id)) {
      return str_replace(
        'public:/',																						//standard file uri is public://.../filename.jpg
        \Drupal::service('stream_wrapper.public')->baseUrl(),	//we want DRUPALHOME/sites/default/.../filename.jpg
        $file_object->getFileUri()
      );
    }
    return NULL;
  }
  
  public function getSchemedUriFromPublicUri($file_uri) {
  
    return str_replace(
      \Drupal::service('stream_wrapper.public')->baseUrl(),
      'public:/',
      $file_uri
    );
  }

  /**
   * This function is called by the Views module.
   */
  public function getTableMapping(array $storage_definitions = NULL) {

    $definitions = $storage_definitions ? : \Drupal::getContainer()->get('entity.manager')->getFieldStorageDefinitions($this->entityTypeId);
/*
    if (!empty($definitions)) {
      if (\Drupal::moduleHandler()->moduleExists('devel')) {
        #dpm($definitions,__METHOD__);
      } else drupal_set_message('Non-empty call to '.__METHOD__);
    }
*/

    $table_mapping = $this->tableMapping;

/*
    // Here we should get a new DefaultTableMapping
    // this has to be integrated... @todo    
    if (!isset($this->tableMapping)) {
      $table_mapping = new DefaultTableMapping($this->entityType, $definitions); 
    }
    
    $dedicated_table_definitions = array_filter($definitions, function (FieldStorageDefinitionInterface $definition) use ($table_mapping) {
      return $table_mapping
        ->requiresDedicatedTableStorage($definition);
    });

    dpm($dedicated_table_definitions, "dpm");
    
    $this->tableMapping = $table_mapping;
*/
    return $table_mapping;
  }

  /**
   * {@inheritdoc}
   */
//  public function load($id) {
//    //@TODO load WisskiEntity here
//  }

  /**
   * {@inheritdoc}
   */
#  public function loadRevision($revision_id) {
#    return NULL;
#  }

  /**
   * {@inheritdoc}
   */
#  public function deleteRevision($revision_id) {
#  }

  /**
   * {@inheritdoc}
   */
#  public function loadByProperties(array $values = array()) {
#    
#    return array();
#  }

  /**
   * {@inheritdoc}
   */
#  public function delete(array $entities) {
#  }

  /**
   * {@inheritdoc}
   */
#  protected function doDelete($entities) {
#  }

  /**
   * {@inheritdoc}
   */
/*
  public function save(EntityInterface $entity) {
#    drupal_set_message("I am saving, yay!" . serialize($entity->id()));
    return parent::save($entity);
  }
*/
  /**
   * {@inheritdoc}
   */
  protected function getQueryServiceName() {
    return 'entity.query.wisski_core';
  }

  /**
   * {@inheritdoc}
   * @TODO must be implemented
   */
  protected function doLoadRevisionFieldItems($revision_id) {
  }

  /**
   * {@inheritdoc}
   * @TODO must be implemented
   */
  protected function doSaveFieldItems(ContentEntityInterface $entity, array $names = []) {
#    \Drupal::logger('WissKIsaveProcess')->debug(__METHOD__ . " with values: " . serialize(func_get_args()));
#    \Drupal::logger('WissKIsaveProcess')->debug(serialize($entity->uid->getValue())); 
#    dpm(func_get_args(),__METHOD__);
#    return;

#    dpm($entity->uid->getValue(), 'uid');

    $moduleHandler = \Drupal::service('module_handler');
    if (!$moduleHandler->moduleExists('wisski_pathbuilder')){
      return NULL;
    }
                      

    $uid = $entity->uid;
    // override the user setting
    if(isset($uid) && empty($uid->getValue()['target_id']) ) {
      $user = \Drupal::currentUser();
#    dpm($values, "before");
      $uid->setValue(array('target_id' => (int)$user->id()));
    }
    
#    dpm($entity->uid->getValue(), 'uid');


    // gather values with property caching
    // set second param of getValues to FALSE: we must not write
    // field values to cache now as there may be no eid yet (on create)

    list($values,$original_values) = $entity->getValues($this,FALSE);
    $bundle_id = $values['bundle'][0]['target_id'];
    if (empty($bundle_id)) $bundle_id = $entity->bundle();
    // TODO: What shall we do if bundle_id is still empty. Can this happen?

    // we only load the pathbuilders and adapters that can handle the bundle.
    // Loading all of them would take too long and most of them don't handle
    // the bundle, assumingly.
    // We have this information cached.
    // Then we filter the writable ones
    $pbs_info = \Drupal::service('wisski_pathbuilder.manager')->getPbsUsingBundle($bundle_id);
    $adapters_ids = array();
    $pb_ids = array();
    foreach($pbs_info as $pbid => $info) {
      if ($info['writable']) {
        $aid = $info['adapter_id'];
        $pb_ids[$pbid] = $pbid;
        $adapter_ids[$aid] = $aid;
      }
      elseif ($info['preferred_local']) {
        // we warn here as the peferred local store should be writable if an 
        // entity is to be saved. Eg. the sameAs mechanism relies on that.
        drupal_set_message(t('The preferred local store %a is not writable.', array('%a' => $adapter->label())),'warning');
      } 
    }
    // if there are no adapters by now we die...
    if(empty($adapter_ids)) {
      drupal_set_message("There is no writable storage backend defined.", "error");
      return;
    }
    
    $pathbuilders = WisskiPathbuilderEntity::loadMultiple($pb_ids);
    $adapters = Adapter::loadMultiple($adapter_ids);

    
    $entity_id = $entity->id();

    // we track if this is a newly created entity, if yes, we want to write it to ALL writable adapters
    $create_new = $entity->isNew() && empty($entity_id);
    $init = $create_new;
    
    // if there is no entity id yet, we register the new entity
    // at the adapters
    if (empty($entity_id)) {    
      foreach($adapters as $aid => $adapter) {
        $entity_id = $adapter->createEntity($entity);
        $create_new = FALSE;
      }
    }
    if (empty($entity_id)) {
      drupal_set_message('No local adapter could create the entity','error');
      return;
    }
    
    // now we should have an entity id and a bundle - so cache it!
    $this->writeToCache($entity_id, $bundle_id);
    
    foreach($pathbuilders as $pb_id => $pb) {
      
      //get the adapter
      $aid = $pb->getAdapterId();
      $adapter = $adapters[$aid];

      $success = FALSE;
#      drupal_set_message("I ask adapter " . serialize($adapter) . " for id " . serialize($entity->id()) . " and get: " . serialize($adapter->hasEntity($id)));
      // if they know that id
      // Martin: The former if test excluded the case where the entity was
      // there already but the adapter had no information about it, so that
      // nothing is saved in this case (into this store!). This means that
      // for an existing entity nothing can be added on save. This is the case
      // e.g. when an entity from an authority is only referred to first and
      // later someone wants to add local information. This was not possible 
      // with this if. Thats why we always want it to be true.
      if(TRUE || $create_new || $adapter->hasEntity($entity_id)) {
        
        // perhaps we have to check for the field definitions - we ignore this for now.
        //   $field_definitions = $this->entityManager->getFieldDefinitions('wisski_individual',$bundle_idid);
        try {
          //we force the writable adapter to write values for newly created entities even if unknown to the adapter by now
          //@TODO return correct success code
          $adapter_info = $adapter->writeFieldValues($entity_id, $values, $pb, $bundle_id, $original_values,$create_new, $init);

          // By Mark: perhaps it would be smarter to give the writeFieldValues the entity
          // object because it could make changes to it
          // e.g. which uris were used for reference (disamb) etc.
          // as long as it is like now you can't promote uris back to the storage.
          // By Martin: this is an important point. Also the adapters should propagate
          // disamb/all ?xX uris also when loading as there is no way to trace the value
          // otherwise.

          $success = TRUE;
        } catch (\Exception $e) {
          drupal_set_message('Could not write entity into adapter '.$adapter->id() . ' because ' . serialize($e->getMessage()));
          throw $e;
        }
      } else {
        //drupal_set_message("No, I don't know " . $id . " and I am " . $aid . ".");
      }
      
      if ($success) {
        
        // TODO: why are the next two necessary? what do they do?
        $entity->set('eid',$entity_id);
        $entity->enforceIsNew(FALSE);
        //we have successfully written to this adapter

        // write values and weights to cache table
        // we reuse the getValues function and set the second param to true
        // as we are not interested in the values we discard them
        $entity->getValues($this, TRUE);
        // TODO: eventually there should be a seperate function for the field caching
        
      }
    }

    $bundle = \Drupal\wisski_core\Entity\WisskiBundle::load($bundle_id);
    if ($bundle) $bundle->flushTitleCache($entity_id);

  }

  /**
   * {@inheritdoc}
   * @TODO must be implemented
   */
  protected function doDeleteFieldItems($entities) {

    $moduleHandler = \Drupal::service('module_handler');
    if (!$moduleHandler->moduleExists('wisski_pathbuilder')){
      return NULL;
    }
                      

    $local_adapters = array();
    $writable_adapters = array();
    $delete_adapters = array(); // adapters that we use for deleting the entities
    $all_adapters = entity_load_multiple('wisski_salz_adapter');

    foreach($all_adapters as $aid => $adapter) {
      // we locate all writable stores
      // then we locate all local stores in these writable stores

      if($adapter->getEngine()->isWritable())
        $writable_adapters[$aid] = $adapter;
             
      if($adapter->getEngine()->isPreferredLocalStore())
        $local_adapters[$aid] = $adapter;
      
    }
    // if there are no adapters by now we die...
    if(empty($writable_adapters)) {
      drupal_set_message("There is no writable storage backend defined.", "error");
      drupal_set_message("No writable storage backend defined.", "error");
      return;
    }
    
    if($diff = array_diff_key($local_adapters,$writable_adapters)) {
      if (count($diff) === 1)
        drupal_set_message('The preferred local store '.key($diff).' is not writable','warning');
      else drupal_set_message('The preferred local stores '.implode(', ',array_keys($diff)).' are not writable','warning');
    }
    
    //we load all pathbuilders, check if they know the fields and have writable adapters
    $pathbuilders = WisskiPathbuilderEntity::loadMultiple();

    foreach($pathbuilders as $pb_id => $pb) {
      $aid = $pb->getAdapterId();
      //check, if it's writable, if not we can stop here
      if (isset($writable_adapters[$aid])) {
        $delete_adapters[$aid] = $writable_adapters[$aid];
      }
    }
    
    foreach($entities as $entity) {
      foreach ($delete_adapters as $adapter) {
        $return = $adapter->deleteEntity($entity);
      }
      AdapterHelper::deleteUrisForDrupalId($entity->id());
      WisskiCacheHelper::flushCallingBundle($entity->id());
    }

    if (empty($return)) {
      drupal_set_message('No local adapter could delete the entity','error');
      return;
    }
  }

  /**
   * {@inheritdoc}
   * @TODO must be implemented
   */
  protected function doDeleteRevisionFieldItems(ContentEntityInterface $revision) {
  }

  /**
   * {@inheritdoc}
   * @TODO must be implemented
   */
  protected function readFieldItemsToPurge(FieldDefinitionInterface $field_definition, $batch_size) {
    return array();
  }

  /**
   * {@inheritdoc}
   * @TODO must be implemented
   */
  protected function purgeFieldItems(ContentEntityInterface $entity, FieldDefinitionInterface $field_definition) {
  }

  /**
   * {@inheritdoc}
   */
#  protected function doSave($id, EntityInterface $entity) {
#  }

  /**
   * {@inheritdoc}
   * @TODO must be implemented
   */
  protected function has($id, EntityInterface $entity) {
    
    if ($entity->isNew()) return FALSE;
    $adapters = entity_load_multiple('wisski_salz_adapter');
    // ask all adapters
    foreach($adapters as $aid => $adapter) {
      if($adapter->getEngine()->hasEntity($id)) {
        return TRUE;
      }            
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   * @TODO must be implemented
   */
  public function countFieldData($storage_definition, $as_bool = FALSE) {
    //@TODO return the truth
    return $as_bool ? FALSE : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function hasData() {
    //@TODO this is only for development purposes. So we can uninstall the module without having to delete data
    return FALSE;
  }  
  
  public function writeToCache($entity_id,$bundle_id) {
    try {
      WisskiCacheHelper::putCallingBundle($entity_id,$bundle_id);
    } catch (\Exception $e) {
#dpm(func_get_args(), 'writeToCache');
    }
  }
  
  // WissKI image preview stuff.

    /**
   * externally prepare the preview images
   * this is necessary e.g. for views
   * @return returns true on sucess, false else.
   */
  public function preparePreviewImages() {
    $pref_local = \Drupal\wisski_salz\AdapterHelper::getPreferredLocalStore();
    if (!$pref_local) {
      $conf_adapter = \Drupal::config('wisski_core.settings')->get('preview_image_adapters');
      
      if(!empty($conf_adapter)) {
        $this->preview_image_adapters = $conf_adapter;
        return TRUE;
      }
      
      drupal_set_message("No store for preview images was found. Please select one in the configuration.", "warning");
      
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

  /**
   * this gathers the URI i.e. some public:// or remote path to this entity's
   * preview image
   */
  public function getPreviewImageUri($entity_id,$bundle_id) {
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

    $found_preview = FALSE;

    // we iterate through all the selected adapters but we stop at the first
    // image that was successfully converted to preview image style as we only
    // need one!
    foreach ($this->preview_image_adapters as $adapter_id => $adapter) {
      
      if ($adapter === NULL || !is_object($adapter)) {
        // we lazy-load adapters
#        dpm("4.2.99: " . microtime());
        $adapter = entity_load('wisski_salz_adapter', $adapter_id);
#        dpm("4.2.999: " . microtime());
        if (empty($adapter)) {
          unset($this->preview_image_adapters[$adapter_id]);
          continue;
        } else {
          $this->preview_image_adapters[$adapter_id] = $adapter;
        }
      }
#      dpm(microtime(), "is_get_uris_evil?");
      if (empty(\Drupal\wisski_salz\AdapterHelper::getUrisForDrupalId($entity_id, $adapter->id(), FALSE))) {
        // this is wrong here - any other backend might know the image!
        /*
        if (WISSKI_DEVEL) \Drupal::logger('wisski_preview_image')->debug($adapter->id().' does not know the entity '.$entity_id);
        WisskiCacheHelper::putPreviewImageUri($entity_id,'none');
        return NULL;
        */
        continue;
      }

      //ask the local adapter for any image for this entity
#      $images = $adapter->getEngine()->getImagesForEntityId($entity_id,$bundle_id);
      $images = array();
#      dpm(microtime(), "in storage1");
      
      $images = \Drupal::service('wisski_pathbuilder.manager')->getPreviewImage($entity_id, $bundle_id, $adapter);
#      dpm(microtime(), "in storage2");

#      $image_field_ids = \Drupal\wisski_core\WisskiHelper::getFieldsForBundleId($bundle_id, 'image', NULL, TRUE);

#      dpm($adapter->getEngine()->loadPropertyValuesForField(current($image_field_ids), array(), array($entity_id => $entity_id)),  "fv!");
      
#      dpm($this->getCacheValues(array($entity_id, )), "cache!");

#      dpm($images, "images");

      if(count($images) > 1) {

        $bids = array();
        $deltas = array();
        $fids = array();
        
        $ever_found_weight = FALSE;

        foreach($images as $image) { 	       

          $to_look_for = $image;

          $old_to_look_for = NULL;
          $fid_to_look_for = NULL;

          $found_weight = FALSE;

          while(!$found_weight && $old_to_look_for != $to_look_for) {

            $old_to_look_for = $to_look_for;

            // get the weight
            $cached_field_values = db_select('wisski_entity_field_properties','f')
              ->fields('f',array('eid', 'fid', 'bid', 'ident','delta','properties'));
#           ->condition('eid',$id)
#           ->condition('bid',$values[$id]['bundle'])
                        
            if(!empty($fid_to_look_for)) {
              $cached_field_values = $cached_field_values->condition('fid', $fid_to_look_for);
            }
            
            $cached_field_values = $cached_field_values->condition('ident', $to_look_for)
              ->execute()
              ->fetchAll();
              
#            dpm($cached_field_values, "looked for: " . $to_look_for);
          
            foreach($cached_field_values as $cfv) {
              // the eid from the image should be the ident of the field
              $to_look_for = $cfv->eid;

              // Mark: this is sloppy
              // in wisski this generally holds
              // however if you do entity reference to the image - this might not hold
              // then it probably should not be in fid, but in the properties or something
              // there you will have to change something in this case!!!              
              $fid_to_look_for = $cfv->bid;
              
              // delta is the weight
              $deltas[$image] = intval($cfv->delta);              
            }
            
            // didn't find anything?
            if(empty($deltas) || empty($deltas[$image])) {
              $deltas[$image] = 0;
//              $found_weight = TRUE;
            }
 
            // did we find a weight?           
            if($deltas[$image] != 0) {
              $found_weight = TRUE;
              $ever_found_weight = TRUE;
            }
          }        
        }

#        dpm($image, "image");
#        dpm($deltas, "weight");        

        // sort for weight
        if($ever_found_weight)
          asort($deltas);

#        dpm($found_weight);  
#        dpm($deltas, "after");
        
#        dpm(array_keys($deltas), "ak");
        
        // give out only the lightest one!
        $images = array(current(array_keys($deltas)));
        
                                                           
        
      }
 
      #dpm($images, "out");
      
      #dpm(microtime(), "in storage3");
      
#      dpm($images, "yay");
#    dpm("4.2.4: " . microtime());

      if (empty($images)) {
        if (WISSKI_DEVEL) \Drupal::logger('wisski_preview_image')->debug('No preview images available from adapter '.$adapter->id());
        continue;
      }
      
      $found_preview = TRUE;

      if (WISSKI_DEVEL) \Drupal::logger('wisski_preview_image')->debug("Images from adapter $adapter_id: ".serialize($images));
      //if there is at least one, take the first of them
      //@TODO, possibly we can try something mor sophisticated to find THE preview image
      $input_uri = current($images);
      
      if(empty($input_uri)) {
        if (WISSKI_DEVEL) \Drupal::logger('wisski_preview_image')->debug('No preview images available from adapter '.$adapter->id());
        continue;
      }
      
    #dpm("4.2.4.1: " . microtime());
      //now we have to ensure there is the correct image file on our server
      //and we get a derivate in preview size and we have this derivates URI
      //as the desired output
      $output_uri = '';
      
      //get a correct image uri in $output_uri, by saving a file there
      #$this->storage->getFileId($input_uri,$output_uri);
      // generalized this line for external use
      $this->getFileId($input_uri, $output_uri);
#    dpm("4.2.4.2: " . microtime());
      //try to get the WissKI preview image style
      $image_style = $this->getPreviewStyle();
#    dpm("4.2.5: " . microtime());    
      //process the image with the style
      $preview_uri = $image_style->buildUri($output_uri);
      #dpm(array('output_uri'=>$output_uri,'preview_uri'=>$preview_uri));
      
      // file already exists?
      if(file_exists($preview_uri)) {
#        dpm(microtime(), "file exists!");
        WisskiCacheHelper::putPreviewImageUri($entity_id,$preview_uri);
        #dpm(microtime(), "file exists 2");
        return $preview_uri;
      }
#      dpm($output_uri, "out");
#      dpm($preview_uri, "pre");
#      dpm($image_style->createDerivative($output_uri,$preview_uri), "create!");
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
    
    if(empty($preview_uri) || empty($found_preview)) {
      
      $image_style = $this->getPreviewStyle();
      $output_uri = drupal_get_path('module', 'wisski_core') . "/images/img_nopic.png";
#      dpm($output_uri, "out");
      $preview_uri = $image_style->buildUri($output_uri);
      if ($out = $image_style->createDerivative($output_uri,$preview_uri)) {
        WisskiCacheHelper::putPreviewImageUri($entity_id,$preview_uri);
        return $preview_uri;
      }
    }
    

#    dpm("could not find preview for " . $entity_id);
    return NULL;

  }
  
  
  /**
   * loads and - if necessary - in advance generates the 'wisski_preview' ImageStyle
   * object
   * the style resizes - mostly downsizes - the image and converts it to JPG
   */
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
#wpm($config,'image style config');
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
  
}
