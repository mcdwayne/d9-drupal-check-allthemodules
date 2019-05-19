<?php

namespace Drupal\wisski_core;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity as Pathbuilder;

/**
 * a class that holds static convenient methods to be used by the Wisski modules
 */
class WisskiHelper {

  /**
   * Merges two associative arrays according to their keys. Values for keys that are present in both arrays
   * will be taken from the SECOND one, unless their value is considered empty.
   */
  public static function array_merge_nonempty(array $array1,array $array2) {
    
    $return = $array1;
    foreach ($array2 as $key => $value) {
      if (!empty($value)) $return[$key] = $value;
    }
    return $return;
  }
  
  /**
   * Splits the array in two parts at a given index.
   * Not tested with non-numeric indices. Expect keys to be re-arranged
   * @param $array the array to be split
   * @param $offset the index where to split the array. The entry at $offset will be the first in the
   * second part after splitting i.e. there will we a number of $offset elements in the first part
   * @return an array with two elements representing the first and second part of the original array
   */
  public static function array_split(array $array,$offset=NULL) {
    
    if ($offset==0) return array(array(),$array);
    $count = count($array);
    if ($offset >= $count) return array($array,array());
    if (2*$offset < $count) {
      $rev = array_chunk(array_reverse($array),$count-$offset);
      $out = array();
      foreach ($rev as $chunk) {
        $out[] = array_reverse($chunk);
      }
      $out = array_reverse($out);
      
    } else $out = array_chunk($array,$offset);
    while (count($out) < 2) $out[] = array();
    return $out;
  }

  /**
   * inserts an array as subarray of another numerically indexed array
   * @param $array the array where the portion shall be inserted
   * @param $insertion the portion to be inserted
   * @param $offset the first index of the inserted sub-array after insertion
   * @return a re-indexed array with the subportion inserted
   */
  public static function array_insert(array $array,array $insertion,$offset=NULL) {
    
    if ($offset==0) return array_merge($insertion,$array);
    list($part1,$part2) = self::array_split($array,$offset);
    return array_merge($part1,$insertion,$part2);
  }
  
  /**
   * Removes a portion of a numerically indexed array
   * @param $array the input array
   * @param $offset the first index to be removed
   * @param $the length of the portion to remove
   * @return an array resembling the input but with the specified part removed and re-indexed
   */
  public static function array_remove_part(array $array,$offset,$length=NULL) {
    
    if ($length == 0) return $array;
    list($part1,$part2) = self::array_split($array,$offset);
    list($lost,$part3) = self::array_split($part2,$length);
    return array_merge($part1,$part3);
  }

  /**
   * Gets the top bundles from the pathbuilders of the system
   * @param $get_labels useless
   * @return returns a list of top bundle ids
   */
  public static function getTopBundleIds($get_full_info=FALSE) {
    
    $moduleHandler = \Drupal::service('module_handler');
    if (!$moduleHandler->moduleExists('wisski_pathbuilder')){
      return NULL;
    }
                      
    
    $pbs = \Drupal::entityManager()->getStorage('wisski_pathbuilder')->loadMultiple();
    if (empty($pbs)) return array();
    $parents = array();
    foreach ($pbs as $pb_id => $pb) {
      $pathtree = $pb->getPathTree();
#      dpm($pathtree);
      if (empty($pathtree)) continue;
      $pbarr = $pb->getPbPaths();
      
      foreach($pathtree as $key => $value) {
#        dpm($pbarr[$key], "pbarr");
        
        // in this case it is a field on top level - this should not occur, but we ignore it here!
        if(!empty($pbarr[$key]['field']) && $pbarr[$key]['field'] != $pbarr[$key]['bundle'])
          continue; 
        
        if(!empty($pbarr[$key]['bundle']) && $pbarr[$key]['bundle'] != Pathbuilder::CONNECT_NO_FIELD && $pbarr[$key]['bundle'] != Pathbuilder::GENERATE_NEW_FIELD) {
          $bundle_id = $pbarr[$key]['bundle'];
#          dpm($bundle_id, $pb->id() . ' and key ' . $key);
          
          // skip if empty bundle id
          if(empty($bundle_id))
            continue;
              
          if ($get_full_info) {
            $bundle_ob = \Drupal\wisski_core\Entity\WisskiBundle::load($bundle_id);
            
            // skip if empty object
            if(empty($bundle_ob))
              continue;

            $parents[$bundle_id] = array(
              'label' => $bundle_ob->label(),
              'pathbuilder' => $pb_id,
              'path_id' => $key,
              'bundle' => $bundle_ob,
            );
          } else {
            $parents[$bundle_id] = $bundle_id;
          }
        }
      }
#      $parent_id = $pb->getParentBundleId($bundle_id);
#      if ($parent_id) {
#        if ($get_labels) {
#          $parents[$parent_id] = \Drupal\wisski_core\Entity\WisskiBundle::load($parent_id)->label();
#        } else $parents[$parent_id] = $parent_id;
#      }
    }
    return $parents;
  }  

  /**
   * Gets all fields from a bundle id of a given type and format
   * @param $bundleid	The id of the bundle
   * @param $fieldtype	The type of the field or null for any field
   * @param $field_format	The format of the field or null for any
   */ 
  public static function getFieldsForBundleId($bundleid, $fieldtype = NULL, $field_format = NULL, $recursive = TRUE) {
    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('wisski_individual',$bundleid);
    
    $out = array();
    $subbundles = array();
    
    foreach($field_definitions as $key => $field_definition) {
      if(!($field_definition instanceof BaseFieldDefinition)) {
        
        if(empty($fieldtype))
          $out[$key] = $key;
        else if($fieldtype == $field_definition->getType()) {
          if(empty($field_format))
            $out[$key] = $key;

          
// @TODO This might be useful later - up to now $field format is ignored 
#          $def = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();
          
#          drupal_set_message("def is: " . serialize($def[$field_definition->getType()]));
#          $ding = $def[$field_definition->getType()];
#          $blubb = $ding['class'];
          
#          drupal_set_message("miazt: " . serialize($blubb::propertyDefinitions($field_definition->getFieldStorageDefinition())));
          #else if(Drupal::service('plugin.manager.' . $key . '.' .$field_definition->getType())->getDefinitions())
#            $out[$key] = $key;
        }
        
        // if it is entity_reference - recurse if user wants to         
        if($recursive && $field_definition->getType() == "entity_reference") {
          
#          drupal_set_message("er: " . serialize($field_definition->getSettings()['handler_settings']['target_bundles']));
          $subbundles = array_merge($subbundles, $field_definition->getSettings()['handler_settings']['target_bundles']);
        }
      }
    }
    
    
    // recurse
    if($recursive) {
      foreach($subbundles as $subbundle) {
        $out = array_merge($out, \Drupal\wisski_core\WisskiHelper::getFieldsForBundleId($subbundle, $fieldtype, $field_format, $recursive));
      }
    }
    
    return $out;
  }
  
}

