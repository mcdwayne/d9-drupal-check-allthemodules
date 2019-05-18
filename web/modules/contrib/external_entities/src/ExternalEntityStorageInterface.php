<?php

namespace Drupal\external_entities;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines an interface for external entity entity storage classes.
 */
interface ExternalEntityStorageInterface extends ContentEntityStorageInterface {

  /**
   * Get the storage client.
   *
   * @return \Drupal\external_entities\StorageClient\ExternalEntityStorageClientInterface
   *   The external entity storage client.
   */
  public function getStorageClient();

  /**
   * Gets the external entity type.
   *
   * @return \Drupal\external_entities\ExternalEntityTypeInterface
   *   The external entity type.
   */
  public function getExternalEntityType();

}
