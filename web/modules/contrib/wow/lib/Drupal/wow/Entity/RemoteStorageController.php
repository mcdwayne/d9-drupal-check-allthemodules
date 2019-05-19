<?php

/**
 * @file
 * Definition of Drupal\wow\Entity\RemoteStorageController
 */

namespace Drupal\wow\Entity;

/**
 * This extends the EntityAPIController class, adding required special handling
 * for remote entity objects.
 *
 * To extends this controller, an entity must have the following requirements:
 *   - a 'lastFetched' field, corresponding to the time stamp it was last
 *   fetched from the service, not necessary updated, but at least fetched.
 *   - a refresh function of the form $entity_type . '_refresh' which is
 *   responsible to refresh an existing entity. This function should honor
 *   If-Modified-Since headers if needed, and saves the entity if updated.
 *   Important note: you need to set explicitly the original field before saving
 *   the entity, doing that will prevent an infinite loop between save and load
 *   mechanics.
 *   @see wow_character_refresh()
 *   @see wow_guild_refresh()
 */
abstract class RemoteStorageController extends \EntityAPIController {

  /**
   * The WoW entity refresh method.
   *
   * @var string
   */
  protected $refreshMethod;

  /**
   * The WoW entity refresh threshold.
   *
   * @var string
   */
  protected $refreshThreshold;

  /**
   * (non-PHPdoc)
   * @see DrupalDefaultEntityController::__construct()
   */
  public function __construct($entityType) {
    parent::__construct($entityType);

    $this->refreshMethod = wow_entity_refresh_method($entityType);
    $this->refreshThreshold = wow_entity_refresh_threshold($entityType);
  }

  /**
   * Load an entity from the database.
   *
   * If the refresh method is set to loading time, calls the corresponding hook
   * responsible for refreshing an entity.
   */
  protected function attachLoad(&$queried_entities, $revision_id = FALSE) {
    // Get the entity method for refreshing.
    if ($this->refreshMethod == WOW_REFRESH_LOAD) {

      foreach ($queried_entities as $entity) {
        // Check the lastFetched value before proceeding to an update.
        if ($entity->lastFetched + $this->refreshThreshold < REQUEST_TIME) {
          // For efficiency manually save the original character before applying
          // any changes.
          $entity->original = clone $entity;
          $entity->refresh();
          $entity->save();
        }
      }
    }

    parent::attachLoad($queried_entities, $revision_id);
  }

}
