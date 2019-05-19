<?php

namespace Drupal\wisski_pathbuilder;



class PathbuilderManager {
   
  private static $pbsForAdapter = NULL;
  
  private static $pbsUsingBundle = NULL;

  private static $bundlesWithStartingConcept = NULL;

  private static $imagePaths = NULL;
 
  private static $pbs = NULL;
  
  private static $paths = NULL;
  
  /** Reset the cached mappings.
   */
  public function reset() {
    self::$pbsForAdapter = NULL;
    self::$pbsUsingBundle = NULL;
    self::$imagePaths = NULL;
    self::$pbs = NULL;
    self::$paths = NULL;
    \Drupal::cache()->delete('wisski_pathbuilder_manager_pbs_for_adapter');
    \Drupal::cache()->delete('wisski_pathbuilder_manager_pbs_using_bundle');
    \Drupal::cache()->delete('wisski_pathbuilder_manager_image_paths');
  }
  
  
  /** Get the pathbuilders that make use of a given adapter.
   *  
   * @param adapter_id the ID of the adapter
   * @return if adapter_id is empty, returns an array where the keys are
   *          adapter IDs and the values are arrays of corresponding 
   *          pathbuilders. If adapter_id is given returns an array of 
   *          corresponding pathbuilders.
   */
  public function getPbsForAdapter($adapter_id = NULL) {
    if (self::$pbsForAdapter === NULL) {  // not yet fetched from cache?
      if ($cache = \Drupal::cache()->get('wisski_pathbuilder_manager_pbs_for_adapter')) {
        self::$pbsForAdapter = $cache->data;
      }
    }
    if (self::$pbsForAdapter === NULL) {  // was reset
      self::$pbsForAdapter = array();
      $pbs = entity_load_multiple('wisski_pathbuilder');
      foreach ($pbs as $pbid => $pb) {
        $aid = $pb->getAdapterId();
        $adapter = entity_load('wisski_salz_adapter', $aid);
        if ($adapter) {
          if (!isset(self::$pbsForAdapter[$aid])) {
            self::$pbsForAdapter[$aid] = array();
          }
          self::$pbsForAdapter[$aid][$pbid] = $pbid;
        }
        else {
          drupal_set_message(t('Pathbuilder %pb refers to non-existing adapter with ID %aid.', array(
            '%pb' => $pb->getName(),
            '%aid' => $pb->getAdapterId(),
          )), 'error');
        }
      }
      \Drupal::cache()->set('wisski_pathbuilder_manager_pbs_for_adapter', self::$pbsForAdapter);
    }
    return empty($adapter_id)
           ? self::$pbsForAdapter
           : (isset(self::$pbsForAdapter[$adapter_id])  // if there is no pb for this adapter there is no array key
             ? self::$pbsForAdapter[$adapter_id] 
             : array());                                // ... thus we return an empty array
  }

  
  public function getPbsUsingBundle($bundle_id = NULL) {
    if (self::$pbsUsingBundle === NULL) {  // not yet fetched from cache?
      if ($cache = \Drupal::cache()->get('wisski_pathbuilder_manager_pbs_using_bundle')) {
        self::$pbsUsingBundle = $cache->data;
      }
    }
    if (self::$pbsUsingBundle === NULL) {  // was reset, recalculate
      $this->calculateBundlesAndStartingConcepts();
    }
    return empty($bundle_id) 
           ? self::$pbsUsingBundle // if no bundle given, return all
           : (isset(self::$pbsUsingBundle[$bundle_id]) 
             ? self::$pbsUsingBundle[$bundle_id] // if bundle given and we know it, return only for this
             : array());  // if bundle is unknown, return empty array

  }

  public function getPreviewImage($entity_id, $bundle_id, $adapter) {
    $pbs_and_paths = $this->getImagePathsAndPbsForBundle($bundle_id);
    
#    dpm($pbs_and_paths, "yay!");
    
    foreach($pbs_and_paths as $pb_id => $paths) {
      
      if(empty(self::$pbs)) {
        $pbs = \Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity::loadMultiple();
        self::$pbs = $pbs; 
      } else
        $pbs = self::$pbs;

      $pb = $pbs[$pb_id];      


      $the_pathid = NULL;
      $weight = 99999999999; // beat this ...

      // go through all paths and look for the lowest weight
      foreach($paths as $key => $pathid) {
        $pbp = $pb->getPbPath($pathid);

        if(empty($pbp['enabled']))
          continue;
        
        if(isset($pbp['weight'])) {
          if($pbp['weight'] < $weight) {
            // only take this if the weight is better or the same.
            $the_pathid = $pathid;
            $weight = $pbp['weight'];
          }
        } else if(empty($the_pathid)) {
          // if there was nothing before, something is better at least.
          $the_pathid = $pathid;
        }        
      }
      
#        dpm($pathid, "assa");

      // nothing found?
      if(empty($the_pathid)) {
        return array();
      }
                        
      if(empty(self::$paths)) {
        $paths = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::loadMultiple();
        self::$paths = $paths;
      } else
        $paths = self::$paths;
        
      $path = $paths[$the_pathid];
#      dpm(microtime(), "ptr?");
      $values = $adapter->getEngine()->pathToReturnValue($path, $pb, $entity_id, 0, NULL, FALSE);
#      dpm(microtime(), "ptr!");
      if(!empty($values))
        return $values;

    }
    return array();
  }

  public function getImagePathsAndPbsForBundle($bundle_id) {
 
    if (self::$imagePaths === NULL) {  // not yet fetched from cache?
      if ($cache = \Drupal::cache()->get('wisski_pathbuilder_manager_image_paths')) {
        self::$imagePaths = $cache->data;
      }
    }
    if (self::$imagePaths === NULL) {  // was reset, recalculate
      $this->calculateImagePaths();
    }
     
    if(isset(self::$imagePaths[$bundle_id]))
      return self::$imagePaths[$bundle_id];
    
    return array();
 
  }
  
  public function calculateImagePaths() {
    $info = array();

#    $pbs = entity_load_multiple('wisski_pathbuilder');

    if(empty(self::$pbs)) {
      $pbs = \Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity::loadMultiple();
      self::$pbs = $pbs;
    } else
      $pbs = self::$pbs;

    foreach ($pbs as $pbid => $pb) {
      $groups = $pb->getMainGroups();

      foreach($groups as $group) {
        $bundleid = $pb->getPbPath($group->id())['bundle'];
        $paths = $pb->getImagePathIDsForGroup($group->id());

        if(!empty($paths))
          self::$imagePaths[$bundleid][$pbid] = $paths;

#        foreach($paths as $pathid) {
#          $path = \Drupal\wisski_pathbuilder\Entity\WisskiPathEntity::load($pathid);
#          $info[$bundleid][$pbid][$pathid] = $pathid;
#        }
      }
    }
    
    \Drupal::cache()->set('wisski_pathbuilder_manager_image_paths', self::$imagePaths);
  }

  public function getBundlesWithStartingConcept($concept_uri = NULL) {
    if (self::$bundlesWithStartingConcept === NULL) {  // not yet fetched from cache?
      if ($cache = \Drupal::cache()->get('wisski_pathbuilder_manager_bundles_with_starting_concept')) {
        self::$bundlesWithStartingConcept = $cache->data;
      }
    }
    if (self::$bundlesWithStartingConcept === NULL) {  // was reset, recalculate
      $this->calculateBundlesAndStartingConcepts();
    }
    return empty($concept_uri) 
           ? self::$bundlesWithStartingConcept // if no concept given, return all
           : (isset(self::$bundlesWithStartingConcept[$concept_uri]) 
             ? self::$bundlesWithStartingConcept[$concept_uri] // if concept given and we know it, return only for this
             : array());  // if concept is unknown, return empty array

  }


  private function calculateBundlesAndStartingConcepts() {
    self::$pbsUsingBundle = array();
    self::$bundlesWithStartingConcept = array();
    
    if(empty(self::$pbs)) {
      $pbs = \Drupal\wisski_pathbuilder\Entity\WisskiPathbuilderEntity::loadMultiple();
      self::$pbs = $pbs;
    } else
      $pbs = self::$pbs;
    
    foreach ($pbs as $pbid => $pb) {
      foreach ($pb->getAllGroups() as $group) {
        $pbpath = $pb->getPbPath($group->getID());
        $bid = $pbpath['bundle'];
        if (!empty($bid)) {
          if (!isset(self::$pbsUsingBundle[$bid])) {
            self::$pbsUsingBundle[$bid] = array();
          }
          $adapter = entity_load('wisski_salz_adapter', $pb->getAdapterId());
          if ($adapter) {
            // struct for pbsUsingBundle
            if (!isset(self::$pbsUsingBundle[$bid][$pbid])) {
              $engine = $adapter->getEngine();
              $info = array(
                'pb_id' => $pbid,
                'adapter_id' => $adapter->id(),
                'writable' => $engine->isWritable(),
                'preferred_local' => $engine->isPreferredLocalStore(),
                'engine_plugin_id' => $engine->getPluginId(),
                'main_concept' => array(), // filled below
                'is_top_concept' => array(), // filled below
                'groups' => array(), // filled below
              );
              self::$pbsUsingBundle[$bid][$pbid] = $info;
            }
            $path_array = $group->getPathArray();
            $main_concept = end($path_array); // the last concept is the main concept  
            self::$pbsUsingBundle[$bid][$pbid]['main_concept'][$main_concept] = $main_concept;
            if (empty($pbpath['parent'])) {
              self::$pbsUsingBundle[$bid][$pbid]['is_top_concept'][$main_concept] = $main_concept;
            }
            self::$pbsUsingBundle[$bid][$pbid]['groups'][$group->getID()] = $main_concept;
            
            // struct for bundlesWithStartingConcept
            if (!isset(self::$bundlesWithStartingConcept[$main_concept])) {
              self::$bundlesWithStartingConcept[$main_concept] = array();
            }
            if (!isset(self::$bundlesWithStartingConcept[$main_concept][$bid])) {
              self::$bundlesWithStartingConcept[$main_concept][$bid] = array(
                'bundle_id' => $bid,
                'is_top_bundle' => FALSE,
                'pb_ids' => array(),
                'adapter_ids' => array(),
              );
            }
            self::$bundlesWithStartingConcept[$main_concept][$bid]['pb_ids'][$pbid] = $pbid;
            self::$bundlesWithStartingConcept[$main_concept][$bid]['adapter_ids'][$adapter->id()] = $adapter->id();
            if (empty($pbpath['parent'])) {
              self::$bundlesWithStartingConcept[$main_concept][$bid]['is_top_bundle'] = TRUE;
            }

          }
          else {
            drupal_set_message(t('Pathbuilder %pb refers to non-existing adapter with ID %aid.', array(
              '%pb' => $pb->getName(),
              '%aid' => $pb->getAdapterId(),
            )), 'error');
          }
        }
      }
    }
    \Drupal::cache()->set('wisski_pathbuilder_manager_pbs_using_bundle', self::$pbsUsingBundle);
    \Drupal::cache()->set('wisski_pathbuilder_manager_bundles_with_starting_concept', self::$bundlesWithStartingConcept);
  }

  
  public function getOrphanedPaths() {

    $pba = entity_load_multiple('wisski_pathbuilder');
    $pa = entity_load_multiple('wisski_path');
    $tree_path_ids = array(); // filled in big loop
    
    $home = array(); // here go regular paths, ie. that are in a pb's path tree
    $semiorphaned = array(); // here go paths that are listed in a pb but not in its path tree (are "hidden")
    $orphaned = array(); // here go paths that aren't mentioned in any pb
    
    foreach ($pa as $pid => $p) {
      $is_orphaned = TRUE;
      foreach ($pba as $pbid => $pb) {
        if (!isset($tree_path_ids[$pbid])) {
          $tree_path_ids[$pbid] = $this->getPathIdsInPathTree($pb);
        }
        $pbpath = $pb->getPbPath($pid);
        if (isset($tree_path_ids[$pbid][$pid])) {
          $home[$pid][$pbid] = $pbid;
          $is_orphaned = FALSE;
        }
        elseif (!empty($pbpath)) {
          $semiorphaned[$pid][$pbid] = $pbid;
          $is_orphaned = FALSE;
        }
      }
      if ($is_orphaned) {
        $orphaned[$pid] = $pid;
      }
    }
    return array(
      'home' => $home,
      'semiorphaned' => $semiorphaned,
      'orphaned' => $orphaned,
    );

  } 


  public function getPathIdsInPathTree($pb) {
    $ids = array();
    $agenda = $pb->getPathTree();
    while ($node = array_shift($agenda)) {
      $ids[$node['id']] = $node['id'];
      $agenda = array_merge($agenda, $node['children']);
    }
    return $ids;
  }




}
