<?php

namespace Drupal\entity_import\Plugin\migrate\source;

/**
 * Define the entity import source limit iterator base class.
 */
abstract class EntityImportSourceLimitIteratorBase extends EntityImportSourceBase implements EntityImportSourceLimitIteratorInterface {

  /**
   * @var int
   */
  protected $limitCount = -1;
  /**
   * @var int
   */
  protected $limitOffset = 0;

  /**
   * {@inheritDoc}
   */
  public function setLimitCount($limit) {
    $this->limitCount = $limit;

    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function setLimitOffset($offset) {
    $this->limitOffset = $offset;

    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function resetBaseIterator() {
    $this->iterator = NULL;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    return new \LimitIterator(
      $this->limitedIterator(), $this->limitOffset, $this->limitCount
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLimitIteratorCount() {
    return $this->count();
  }
}
