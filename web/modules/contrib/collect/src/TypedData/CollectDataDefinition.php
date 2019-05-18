<?php
/**
 * @file
 * Contains \Drupal\collect\Plugin\DataType\CollectDataDefinition.
 */

namespace Drupal\collect\TypedData;

use Drupal\collect\Model\ModelTypedDataInterface;
use Drupal\collect\Model\PropertyDefinition;
use Drupal\collect\Query\QueryEvaluatorInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;

/**
 * Defines data of a container.
 */
class CollectDataDefinition extends ComplexDataDefinitionBase implements CollectDataDefinitionInterface {

  /**
   * The model plugin.
   *
   * @var \Drupal\collect\Model\ModelTypedDataInterface
   */
  protected $modelTypedData;

  /**
   * The query evaluator.
   *
   * @var \Drupal\collect\Query\QueryEvaluatorInterface
   */
  protected $queryEvaluator;

  /**
   * {@inheritdoc}
   */
  public function setModelTypedData(ModelTypedDataInterface $model_plugin) {
    $this->modelTypedData = $model_plugin;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getModelTypedData() {
    return $this->modelTypedData;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueryEvaluator(QueryEvaluatorInterface $query_evaluator) {
    $this->queryEvaluator = $query_evaluator;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryEvaluator() {
    return $this->queryEvaluator;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      if (!$this->getModelTypedData()) {
        throw new \BadMethodCallException('No model plugin set.');
      }
      $property_definitions = $this->getModelTypedData()->getPropertyDefinitions();
      // The Collect concept of a property definition includes a query, while
      // the Typed Data API concept with the same name is just a data
      // definition.
      $this->propertyDefinitions = array_map(function(PropertyDefinition $property_definition) {
        return $property_definition->getDataDefinition();
      }, $property_definitions);
      // Add the magic _container property.
      $container_definition = EntityDataDefinition::create('collect_container')
        ->setLabel(\Drupal::entityManager()->getDefinition('collect_container')->getLabel());
      $this->propertyDefinitions = [CollectDataInterface::CONTAINER_KEY => $container_definition] + $this->propertyDefinitions;
    }
    return $this->propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery($property_name) {
    $property_definitions = $this->modelTypedData->getPropertyDefinitions();
    if (isset($property_definitions[$property_name])) {
      return $property_definitions[$property_name]->getQuery();
    }
    throw new \InvalidArgumentException(SafeMarkup::format('Property @name does not exist.', ['@name' => $property_name]));
  }

  /**
   * Determines whether the data is read-only.
   *
   * @return bool
   *   Always TRUE for collect data.
   */
  public function isReadOnly() {
    return TRUE;
  }

}
