<?php

namespace Drupal\wisski_merge;

use \Drupal\wisski_salz\AdapterHelper;
use \Drupal\wisski_salz\Entity\Adapter;
use \Drupal\wisski_salz\Plugin\wisski_salz\Engine\Sparql11Engine;

class Merger {
  
  protected $variables = 'gso';

  protected $adapters = NULL;
  
  public function checkBundles(array $entity_ids) {
    
    AdapterHelper::getBundleIdsForEntityId($entity_id, $only_top_bundles); 


  }
  
  
  protected function sanitizeFrom(array $from, $to) {
    // make from_eids an array with unique items and sort out to
    if (empty($from)) return array();
    $from = (array) $from;
    $from = array_flip($from);
    if (isset($from[$to])) {
      unset($from[$to]);
    }
    $from = array_flip($from);
    return $from;
  }

  
  // Checks if the 
  public function checkIfPartialMerge(array $eids) {

    $eids = $this->sanitizeFrom($eids, '');
    if (empty($eids)) {
      // there is nothing to do...
      return TRUE;
    }
    
    // we determine whether a not supported adapter holds some data for the
    // given entities by checking if there are URIs and if there are triples
    $partial_merge = array();
    $adapters = Adapter::loadMultiple();
    $uris_by_eid_aid = array();
    foreach ($eids as $eid) {
      $uris_by_aid = AdapterHelper::getUrisForDrupalId($eid);
      // do through all adapters that know a URI for this eid
      foreach ($uris_by_aid as $aid => $uri) {
        $adapter = $adapters[$aid];
        if ($this->doesAdapterSupportMerge($adapter)) {
          // we are only interested in adapters that do not support a merge
          continue;
        }
        foreach ($eids as $eid) {
          if (isset($uris_by_aid[$eid][$aid])) {
            $uri = $uris_by_aid[$eid][$aid];
            if ($adapter->getEngine()->checkUriExists($uri)) {
              if (!isset($partial_merge[$eid])) {
                $partial_merge[$eid] = array();
              }
              $partial_merge[$eid][] = $aid;
            }
          }
        }
      }
    }

    return array_keys($partial_merge);

  }

  
  /** Merge one or multiple entities into another entity by updating all 
   * triples/quads associated with these entities in all adapters. As such, the
   * merge can only be done completely on writable triple stores.
   *
   * NOTE: This function does not check whether a merge can be done only 
   * partially nor if the entities somehow fit together (same bundle, etc.)
   * See checkIfPartialMerge() or checkBundles() for that.
   *
   * @param from_eids an array of ids of entity that will be merged. Can also be 
   *                  a scalar with one single entity id
   * @param to_eid the id of the entity that the other will be merged with
   * @param options array of options. Can contain
   *                'delete': boolean whether to delete the merged entities, 
   *                          i.e. whether to perform a copy or move.
   *                          Defaults to TRUE.
   *
   * @return TRUE on success, FALSE otherwise
   */
  public function mergeEntities(array $from_eids, $to_eid, $options = array()) {

#    dpm($from_eids, "from");
#    dpm($to_eid, "to");
#    dpm($options, "options");
#    return;
    
    if (empty($to_eid)) {
      return FALSE;
    }
    $from_eids = $this->sanitizeFrom($from_eids, $to_eid);
    if (empty($from_eids)) {
      // there is nothing to do...
      return TRUE;
    }

    $delete = !(isset($options['delete']) && !$options['delete']);
#dpm(array($delete), 'del');
    
    // the merge is structured as follows:
    // 1. backup the uris associated with the to entity.
    // 2. go thru the adapters and COPY all quads containing the from entities'
    //    uris to the to entity's uris
    // 3. restore the backup'ed uris for the to entity. (the triples for the
    //    from entities will be rewritten so that all from entities' ids also
    //    point to the to entity. This must be deleted and restored)
    // 4. delete the from entities programmatically, so that hooks and 
    //    stuff get called. 
    // 5. load and save the to entity to fire update hooks
    
    // backup to uris
    $to_uris_backup = AdapterHelper::getUrisForDrupalId($to_eid);
#dpm($to_uris_backup, "to backup");

    // do the copying 
    $quad_count = 0;
    $adapters = Adapter::loadMultiple();

    // track if we found something - if we didn't we don't delete!!!
    $foundsomething = FALSE;

    foreach ($adapters as $aid => $adapter) {
      if (!$this->doesAdapterSupportMerge($adapter)) {
        continue;
      }
      $to_uri = AdapterHelper::getUrisForDrupalId($to_eid, $adapter);
      $from_uris = array();
      foreach ($from_eids as $from_eid) {
        $from_uris[] = AdapterHelper::getUrisForDrupalId($from_eid, $adapter);
      }
      if (empty($from_uris)) {
        continue;
      }
#dpm(array($to_uri, $from_uris, $aid));
#return;
      $engine = $adapter->getEngine();
      $quads = $engine->getQuadsContainingUris($from_uris, 'gso');

#      dpm($quads, "quads");
#      return;
      

      if (empty($quads)) {
        continue;
      }
      $foundsomething = TRUE;
      $quad_count += count($quads);

#      dpm($quads, "quads");
#      return;

      if (empty($to_uri)) {
        return t("No URI to map to for adapter %a", array('%a' => $adapter->label()));
      }
      $engine->replaceUris($from_uris, $to_uri, 'gso', TRUE);
      // log this
      $this->logToDB($aid, 'gso', $from_uris, $to_uri, $quad_count, $quads);
    }

    if(!$foundsomething)
      drupal_set_message("Merge could not find data... aborting", "warning");
    
    // do the delete
    if ($delete && $foundsomething) {
      $entities = entity_load_multiple('wisski_individual', $from_eids);
#dpm("do delete count " . count($entities));
      foreach ($entities as $entity) {
#dpm("do delete " . $entity->id());
        $entity->delete();
      }
    }

    $entity = entity_load('wisski_individual', $to_eid);
    $entity->save();

    return TRUE;

  }


  public function doesAdapterSupportMerge(Adapter $adapter) {
    if (empty($adapter)) {
      return FALSE;
    }
    if (!$adapter->isWritable()) {
      return FALSE;
    }
    $engine = $adapter->getEngine();
    if ($engine instanceof Sparql11Engine) {
      return TRUE;
    }
    return FALSE;
  }


  public function logToDB($adapter_id, $variables, $from_uris, $to_uri, $db_count, $db_quads) {
    \Drupal::database()->insert('wisski_merge_log')->fields(
      array(
        'adapter_id' => $adapter_id,
        'variables' => $variables,
        'to_uri' => $to_uri,
        'from_uris' => serialize($from_uris),
        'affected_count' => $db_count,
        'affected_quads' => serialize($db_quads),
        'timestamp' => time(),
      )
    )->execute();
  }

  
  
}
