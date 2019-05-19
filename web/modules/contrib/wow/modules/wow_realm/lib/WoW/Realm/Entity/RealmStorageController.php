<?php

/**
 * @file
 * Contains RealmStorageController.
 */

namespace WoW\Realm\Entity;

use EntityAPIController;

/**
 * Defines a base entity storage controller class.
 */
class RealmStorageController extends EntityAPIController {

  /**
   * Returns an array of realm's id keyed by slug.
   *
   * @param string $region
   *   The region to search for.
   * @param array $slugs
   *   (Optional) An indexed array of realm's slugs.
   *
   * @return array
   *   An array of realm's id keyed by slug.
   */
  public function loadIdsBySlug($region, array $slugs = array()) {
    $select = db_select($this->entityInfo['base table'], 'r')
      ->fields('r', array('slug', $this->idKey))
      ->condition('region', $region);

    if (!empty($slugs)) {
      // If a list of realms has been provided, condition the request.
      $select->condition('slug', $slugs, 'IN');
    }

    // Loads every known realms from the local database.
    return $select->execute()->fetchAllKeyed();
  }

}
