<?php

namespace Drupal\external_entities\Entity\Query\External;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * The external entities storage entity query class.
 */
class Query extends QueryBase implements QueryInterface {

  /**
   * The parameters to send to the external entity storage client.
   *
   * @var array
   */
  protected $parameters = [];

  /**
   * An array of fields keyed by the field alias.
   *
   * Each entry correlates to the arguments of
   * \Drupal\Core\Database\Query\SelectInterface::addField(), so the first one
   * is the table alias, the second one the field and the last one optional the
   * field alias.
   *
   * @var array
   */
  protected $fields = [];

  /**
   * Array of strings added for the group by clause.
   *
   * Keyed by string to avoid duplicates.
   *
   * @var array
   */
  protected $groupBy = [];

  /**
   * Stores the entity type manager used by the query.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Storage client instance.
   *
   * @var \Drupal\external_entities\StorageClient\ExternalEntityStorageClientInterface
   */
  protected $storageClient;

  /**
   * Constructs a query object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param string $conjunction
   *   - AND: all of the conditions on the query need to match.
   * @param array $namespaces
   *   List of potential namespaces of the classes belonging to this query.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, $conjunction, array $namespaces, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type, $conjunction, $namespaces);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Implements \Drupal\Core\Entity\Query\QueryInterface::execute().
   */
  public function execute() {
    return $this
      ->compile()
      ->addSort()
      ->finish()
      ->result();
  }

  /**
   * Compiles the conditions.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   Returns the called object.
   */
  protected function compile() {
    $this->condition->compile($this);
    return $this;
  }

  /**
   * Adds the sort to the build query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   Returns the called object.
   */
  protected function addSort() {
    // TODO: Add sorting capabilities.
    return $this;
  }

  /**
   * Finish the query by adding fields, GROUP BY and range.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   Returns the called object.
   */
  protected function finish() {
    $this->initializePager();
    return $this;
  }

  /**
   * Executes the query and returns the result.
   *
   * @return int|array
   *   Returns the query result as entity IDs.
   */
  protected function result() {
    if ($this->count) {
      return $this->getStorageClient()->countQuery($this->parameters);
    }

    $start = $this->range['start'] ?? NULL;
    $length = $this->range['length'] ?? NULL;
    $query_results = $this->getStorageClient()->query($this->parameters, $this->sort, $start, $length);
    $result = [];
    foreach ($query_results as $query_result) {
      $id = $query_result[$this->getExternalEntityType()->getFieldMapping('id', 'value')];
      $result[$id] = $id;
    }

    return $result;
  }

  /**
   * Get the storage client for a bundle.
   *
   * @return \Drupal\external_entities\StorageClient\ExternalEntityStorageClientInterface
   *   The external entity storage client.
   */
  protected function getStorageClient() {
    if (!$this->storageClient) {
      $this->storageClient = $this->getExternalEntityType()->getStorageClient();

    }
    return $this->storageClient;
  }

  /**
   * Implements the magic __clone method.
   *
   * Reset fields and GROUP BY when cloning.
   */
  public function __clone() {
    parent::__clone();
    $this->fields = [];
    $this->groupBy = [];
  }

  /**
   * Set a parameter.
   *
   * @param string $key
   *   The parameter key.
   * @param mixed $value
   *   The parameter value.
   * @param string|null $operator
   *   (optional) The parameter operator.
   */
  public function setParameter($key, $value, $operator = NULL) {
    $this->parameters[] = [
      'field' => $key,
      'value' => $value,
      'operator' => $operator,
    ];
  }

  /**
   * Gets the external entity type.
   *
   * @return \Drupal\external_entities\ExternalEntityTypeInterface
   *   The external entity type.
   */
  public function getExternalEntityType() {
    return $this
      ->entityTypeManager
      ->getStorage('external_entity_type')
      ->load($this->getEntityTypeId());
  }

}
