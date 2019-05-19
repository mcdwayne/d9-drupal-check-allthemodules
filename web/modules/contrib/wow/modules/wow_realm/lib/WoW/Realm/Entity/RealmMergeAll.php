<?php

/**
 * @file
 * Definition of RealmMergeAll.
 */

namespace WoW\Realm\Entity;

use WoW\Core\CallbackInterface;
use WoW\Core\Response;
use WoW\Core\ServiceInterface;

/**
 * Callback; Merges existing realms with service response.
 */
class RealmMergeAll implements CallbackInterface {

  protected $storage;
  protected $realms;

  public function __construct(RealmStorageController $storage, array $realms = array()) {
    $this->storage = $storage;
    $this->realms = $realms;
  }

  /**
   * (non-PHPdoc)
   * @see \WoW\Core\CallbackInterface::process()
   */
  public function process(ServiceInterface $service, Response $response) {
    // Loads every known realms from the local database.
    // This request is executed to assert if a realm is new or existing.
    $db_rids = $this->storage->loadIdsBySlug($service->getRegion(), $this->realms);

    // Loads the realms from the storage controller.
    $db_realms = $this->storage->load(array_values($db_rids));

    $realms = array();
    $last_fetched = $response->getDate()->getTimestamp();
    foreach ($response->getData('realms') as $values) {
      // Build a look-up array.
      $values += array('lastFetched' => $last_fetched);
      $realms[$values['slug']] = $values;
    }

    // Process the list of realms from the service.
    foreach ($realms as $slug => $values) {
      $rid = array_key_exists($slug, $db_rids) ? $db_rids[$slug] : FALSE;

      if ($rid) {
        $realm = $db_realms[$rid];
        // The realm already exists in the local database. For efficiency
        // manually save the original realm before applying any changes.
        $realm->original = clone $realm;
        $realm->merge($values);

        // Remove realm from the array if they are found. Remaining array
        // contains the list of realms which are not existing anymore.
        unset($db_realms[$rid]);
      }
      else {
        // If the realm is new, adds the region and ask the storage controller
        // to create a new entity.
        $values += array('region' => $service->getRegion());
        $realm = $this->storage->create($values);
      }

      // Permanently save new or existing realm into database.
      $this->storage->save($realm);
    }

    // Deletes the remaining array of realms which is composed of realms
    // present in the database but not in the response from the service.
    $this->storage->delete(array_keys($db_realms));
  }

}
