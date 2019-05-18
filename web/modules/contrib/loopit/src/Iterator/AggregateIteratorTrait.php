<?php

namespace Drupal\loopit\Iterator;

use Drupal\loopit\Aggregate\AggregateInterface;

/**
 * Base implementation of recursive traversal of Aggregate/*.
 */
trait AggregateIteratorTrait {

  /**
   * @var \Drupal\loopit\Aggregate\AggregateArray
   */
  protected $aggregate;

  public function setAggregate(AggregateInterface $aggregate) {
    $this->aggregate = $aggregate;
  }

  /**
   * Create new aggregate instance for children from $this->current() value.
   */
  public function getChildren() {

    $current = $this->current();

    // Notify going down
    $this->aggregate->preDown($this->key());
    // Create children aggregate from $current.
    // Forward options, pass this aggregate as parent.
    $aggregate = $this->aggregate->createInstance($current, $this->aggregate->getOptions(), $this->aggregate);

    return $aggregate->getIterator();
  }

  /**
   * Get the current value from the aggregate.
   */
  public function current() {
    return $this->aggregate->offsetGet($this->key());
  }

  /**
   * Check from the aggregate if the offset exists
   */
  public function valid() {
    $valid = $this->aggregate->offsetExists($this->key());
    if (!$valid && $this->aggregate->getParent()) {

      // Notify going down
      $this->aggregate->preUp();
    }
    return $valid;
  }

  /**
   * Returns true if $current is not empty array or an object.
   *
   * $current can be potentially altered from callbacks.
   */
  public function hasChildren() {

    $current = $this->current();
    // Same parent::hasChildren() but uses potentially altered (from callbacks)
    // $current value. parent::hasChildren() check against the original input
    // passed to the aggregate.
    $has_children = is_array($current) && $current || is_object($current);
    if (!$has_children) {
      // Notify on leaf
      $this->aggregate->onLeaf($current, $this->key());
    }

    return $has_children;
  }
}