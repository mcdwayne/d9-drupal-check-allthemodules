<?php

namespace Drupal\graph_reference;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class LazyEntityLoadIterator
 * @package Drupal\graph_reference
 */
class LazyEntityLoadIterator implements \Iterator {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \ArrayIterator
   */
  protected $pairIterator;

  /**
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $entities = [];

  /**
   * LazyEntityLoadIterator constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param mixed[][] $entity_type_id_pairs
   *   A nested array of entity type & id pairs
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, array $entity_type_id_pairs) {
    $this->entityTypeManager = $entityTypeManager;
    $this->pairIterator = new \ArrayIterator($entity_type_id_pairs);
  }

  /**
   * @param array $pair
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  protected function convert(array $pair) {
    list($entity_type, $id) = $pair;

    if (empty($this->entities[$entity_type][$id])) {
      $entity = $this->entityTypeManager->getStorage($entity_type)->load($id);
      $this->entities[$entity_type][$id] = $entity ? : NULL;
    }

    return $this->entities[$entity_type][$id];
  }

  /**
   * @inheritDoc
   */
  public function current() {
    return $this->convert($this->pairIterator->current());
  }

  /**
   * @inheritDoc
   */
  public function next() {
    $this->pairIterator->next();
  }

  /**
   * @inheritDoc
   */
  public function key() {
    return $this->pairIterator->key();
  }

  /**
   * @inheritDoc
   */
  public function valid() {
    return $this->pairIterator->valid();
  }

  /**
   * @inheritDoc
   */
  public function rewind() {
    $this->pairIterator->rewind();
  }

}