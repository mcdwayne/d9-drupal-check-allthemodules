<?php

namespace Drupal\staged_content\DataProxy;

/**
 * Interface DataProxyInterface.
 *
 * Central interface for all the items that provide an intermediary layer
 * between the data and the storage provided.
 */
interface DataProxyInterface {

  /**
   * Get the entity type.
   *
   * @return string
   *   The entity type.
   */
  public function getEntityType();

  /**
   * Get the actual stored data.
   *
   * @return array
   *   The data in storage.
   */
  public function getData();

  /**
   * Get the actual stored data.
   *
   * @return string
   *   The raw data string in storage.
   */
  public function getRawData();

  /**
   * Get the marker for this item.
   *
   * @return string
   *   The marker for this item.
   */
  public function getMarker();

  /**
   * Get the uuid.
   *
   * @return string
   *   The uuid for this item.
   */
  public function getUuid();

}
