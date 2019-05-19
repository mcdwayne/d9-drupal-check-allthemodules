<?php

/**
 * @file
 * Definition of BattleGroupsMerge.
 */

namespace WoW\Realm\Entity;

use WoW\Core\CallbackInterface;
use WoW\Core\Response;
use WoW\Core\ServiceInterface;

/**
 * Callback; Merges existing realms with service response.
 */
class BattleGroupMerge implements CallbackInterface {

  protected $storage;

  public function __construct(BattleGroupStorageController $storage) {
    $this->storage = $storage;
  }

  /**
   * (non-PHPdoc)
   * @see \WoW\Core\CallbackInterface::process()
   */
  public function process(ServiceInterface $service, Response $response) {
    // Loads every known entities from the local database.
    // This request is executed to assert if a entity is new or existing.
    $db_ids = $this->storage->loadIdsBySlug($service->getRegion());

    // Loads the entities from the storage controller.
    $db_entities = $this->storage->load(array_values($db_ids));

    $service_values = array();
    foreach ($response->getData('battlegroups') as $values) {
      // Build a look-up array.
      $service_values[$values['slug']] = $values;
    }

    // Process the list of battle groups from the service.
    foreach ($service_values as $slug => $values) {
      $id = array_key_exists($slug, $db_ids) ? $db_ids[$slug] : FALSE;

      if ($id) {
        $entity = $db_entities[$id];
        // The battle group already exists in the local database. For efficiency
        // manually save the original battle group before applying any changes.
        $entity->original = clone $entity;
        $entity->merge($values);

        // Remove battle group from the array if they are found. Remaining array
        // contains the list of battle groups which are not existing anymore.
        unset($db_entities[$id]);
      }
      else {
        // If the battle group is new, adds the region and ask the storage
        // controller to create a new entity.
        $values += array('region' => $service->getRegion());
        $entity = $this->storage->create($values);
      }

      // Permanently save new or existing battle group into database.
      $this->storage->save($entity);
    }

    // Deletes the remaining array of battle groups which is composed of battle
    // groups present in the database but not in the response from the service.
    $this->storage->delete(array_keys($db_entities));

    // Sets the expires time stamp.
    $service->setExpires($response, 'wow_battlegroup');
  }

}
