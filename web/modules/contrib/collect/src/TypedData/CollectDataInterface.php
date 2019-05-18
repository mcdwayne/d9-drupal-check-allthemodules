<?php
/**
 * @file
 * Contains \Drupal\collect\TypedData\CollectDataInterface.
 */

namespace Drupal\collect\TypedData;

use Drupal\Core\TypedData\ComplexDataInterface;

/**
 * Defines methods for the CollectData data type.
 */
interface CollectDataInterface extends ComplexDataInterface {

  /**
   * The name of the property holding the container entity.
   */
  const CONTAINER_KEY = '_container';

  /**
   * Returns the data, as parsed by a model plugin.
   *
   * @return mixed
   *   Plugin-parsed data.
   */
  public function getParsedData();

  /**
   * Returns the container whose raw data this typed data represents.
   *
   * @return \Drupal\collect\CollectContainerInterface
   *   The container of the data.
   */
  public function getContainer();

  /**
   * Evaluate a model query on the data.
   *
   * @param string $query
   *   The query to evaluate. The query is evaluated by the model of the
   *   container.
   *
   * @return mixed
   *   The result value of the query.
   */
  public function evaluateQuery($query);

}
