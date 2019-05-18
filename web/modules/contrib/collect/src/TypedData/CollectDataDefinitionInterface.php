<?php
/**
 * @file
 * Contains \Drupal\collect\TypedData\CollectDataDefinitionInterface.
 */

namespace Drupal\collect\TypedData;

use Drupal\collect\Model\ModelTypedDataInterface;
use Drupal\collect\Query\QueryEvaluatorInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;

/**
 * Interface for Collect data definition.
 */
interface CollectDataDefinitionInterface extends ComplexDataDefinitionInterface {

  /**
   * Sets the model plugin defining the data.
   *
   * @param \Drupal\collect\Model\ModelTypedDataInterface $model_plugin
   *   The model plugin.
   *
   * @return $this
   */
  public function setModelTypedData(ModelTypedDataInterface $model_plugin);

  /**
   * Returns the model plugin defining the data.
   *
   * @return \Drupal\collect\Model\ModelTypedDataInterface
   *   The model plugin.
   */
  public function getModelTypedData();

  /**
   * Sets the query evaluator.
   *
   * @param \Drupal\collect\Query\QueryEvaluatorInterface $query_evaluator
   *   The query evaluator.
   *
   * @return $this
   */
  public function setQueryEvaluator(QueryEvaluatorInterface $query_evaluator);

  /**
   * Returns the query evaluator.
   *
   * @return \Drupal\collect\Query\QueryEvaluatorInterface
   *   The query evaluator.
   */
  public function getQueryEvaluator();

  /**
   * Returns the query of a given property.
   *
   * @return string
   *   The query of the property.
   */
  public function getQuery($property_name);
}
