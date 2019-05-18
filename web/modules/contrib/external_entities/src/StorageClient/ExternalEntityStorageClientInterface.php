<?php

namespace Drupal\external_entities\StorageClient;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\external_entities\ExternalEntityInterface;

/**
 * Defines an interface for external entity storage client plugins.
 */
interface ExternalEntityStorageClientInterface extends PluginInspectionInterface, ConfigurablePluginInterface, ContainerFactoryPluginInterface {

  /**
   * Return the name of the external entity storage client.
   *
   * @return string
   *   The name of the external entity storage client.
   */
  public function getName();

  /**
   * Loads raw data for one or more entities.
   *
   * @param array|null $ids
   *   An array of IDs, or NULL to load all entities.
   *
   * @return array
   *   An array of raw data arrays indexed by their IDs.
   */
  public function loadMultiple(array $ids = NULL);

  /**
   * Saves the entity permanently.
   *
   * @param \Drupal\external_entities\ExternalEntityInterface $entity
   *   The entity to save.
   *
   * @return int
   *   SAVED_NEW or SAVED_UPDATED is returned depending on the operation
   *   performed.
   */
  public function save(ExternalEntityInterface $entity);

  /**
   * Deletes permanently saved entities.
   *
   * @param \Drupal\external_entities\ExternalEntityInterface $entity
   *   The external entity object to delete.
   */
  public function delete(ExternalEntityInterface $entity);

  /**
   * Query the external entities.
   *
   * @param array $parameters
   *   (optional) Array of parameters, each value is an array with the following
   *   key-value pairs:
   *     - field: the field name the parameter applies to
   *     - value: the value of the parameter
   *     - operator: the operator of how the parameter should be applied.
   * @param array $sorts
   *   (optional) Array of sorts, each value is an array with the following
   *   key-value pairs:
   *     - field: the field to sort by
   *     - direction: the direction to sort on.
   * @param int|null $start
   *   (optional) The first item to return.
   * @param int|null $length
   *   (optional) The number of items to return.
   */
  public function query(array $parameters = [], array $sorts = [], $start = NULL, $length = NULL);

  /**
   * Query the external entities and return the match count.
   *
   * @param array $parameters
   *   (optional) Key-value pairs of fields to query.
   *
   * @return int
   *   A count of matched external entities.
   */
  public function countQuery(array $parameters = []);

}
