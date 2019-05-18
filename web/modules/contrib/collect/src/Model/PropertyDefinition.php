<?php
/**
 * @file
 * Contains \Drupal\collect\Model\Property.
 */

namespace Drupal\collect\Model;

use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Models a property defined by a model.
 */
class PropertyDefinition {

  /**
   * @var string
   */
  private $query;

  /**
   * @var \Drupal\Core\TypedData\DataDefinitionInterface
   */
  private $dataDefinition;

  function __construct($query, DataDefinitionInterface $data_definition) {
    $this->query = $query;
    $this->dataDefinition = $data_definition;
  }

  /**
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   */
  public function getDataDefinition() {
    return $this->dataDefinition;
  }

  /**
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $data_definition
   */
  public function setDataDefinition($data_definition) {
    $this->dataDefinition = $data_definition;
  }

  /**
   * @return string
   */
  public function getQuery() {
    return $this->query;
  }

  /**
   * @param string $query
   */
  public function setQuery($query) {
    $this->query = $query;
  }

}
