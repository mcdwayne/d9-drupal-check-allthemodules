<?php

/**
 * @file
 * Contains EntityStorageControllerFactory.
 */

namespace WoW\Core\Entity;

/**
 * Defines the EntityStorageControllerFactory.
 *
 * This class is a pure adapter for entity_get_controller.
 */
class EntityStorageControllerFactory {

  /**
   * Gets the entity controller class for an entity type.
   */
  public static function get($entity_type) {
    return entity_get_controller($entity_type);
  }

}
