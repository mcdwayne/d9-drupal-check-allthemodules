<?php

/**
 * @file
 * A boostrap file used in testing.
 */

use Drupal\data_api\DataMock;

if (!class_exists('EntityMalformedException')) {
  /**
   * Defines an exception thrown when a malformed entity is passed.
   */
  class EntityMalformedException extends Exception {

  }
}

if (!function_exists('data_api')) {

  /**
   * Return a DataMock object for unit testing.
   *
   * @param null|string $entity_type
   *   The entity type to use or null.
   *
   * @return \Drupal\data_api\DrupalDataInterface
   *   A \Drupal\data_api\DrupalDataInterface instance.
   */
  function data_api($entity_type = NULL) {
    $obj = new DataMock();

    return $obj->setEntityType($entity_type);
  }

}

if (!function_exists('watchdog_exception')) {

  /**
   * A mocked function.
   *
   * @param string $type
   *   A key for tracking this.
   * @param \Exception $exception
   *   An \Exception instance.
   */
  function watchdog_exception($type, \Exception $exception) {
  }

}
