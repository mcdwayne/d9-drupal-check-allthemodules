<?php

namespace Drupal\staged_content\Storage;

/**
 * Interface to make the way data is stored to the filesystem more uniform.
 */
interface StorageHandlerInterface {

  /**
   * Write away the data for an entity.
   *
   * @param string $data
   *   The normalized data for the entity to store.
   * @param string $entityType
   *   The entity type.
   * @param string $uuid
   *   The original id for the entity (if it has to be stored).
   * @param string $marker
   *   The marker this item is tagged with, for example, prod, acc or test.
   *
   * @TODO remove the NULL option once all exporters add this piece of data.
   */
  public function storeData(string $data, string $entityType, string $uuid, string $marker = NULL);

  /**
   * Detect all the entity types that should be imported.
   *
   * @return \Drupal\staged_content\DataProxy\DataProxyInterface[]
   *   Array of data proxy items to be handled.
   */
  public function listDataItems();

  /**
   * Load all the data for a given file.
   *
   * @param string $entityType
   *   The entity type to load.
   * @param string $uuid
   *   The uuid for the entity to load.
   *
   * @return \Drupal\staged_content\DataProxy\DataProxyInterface
   *   The data item valid for this id.
   */
  public function getDataItem(string $entityType, string $uuid);

}
