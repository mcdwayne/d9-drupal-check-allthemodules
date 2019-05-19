<?php

/**
 * @file
 * Contains BattleGroupStorageController.
 */

namespace WoW\Realm\Entity;

use EntityAPIController;

/**
 * Defines a base entity storage controller class.
 */
class BattleGroupStorageController extends EntityAPIController {

  /**
   * Returns an array of battlegroup's id keyed by slug.
   *
   * @param string $region
   *   The region to search for.
   *
   * @return array
   *   An array of battlegroup's id keyed by slug.
   */
  public function loadIdsBySlug($region) {
    return db_select($this->entityInfo['base table'], 'bg')
      ->fields('bg', array('slug', $this->idKey))
      ->condition('region', $region)
      ->execute()
      ->fetchAllKeyed();
  }

}
