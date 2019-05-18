<?php

namespace Drupal\loopit\Iterator;

use Drupal\loopit\Aggregate\AggregateInterface;

class AggregateFilterIterator extends \RecursiveFilterIterator implements AggregateIteratorInterface {
  use AggregateIteratorTrait;

  public function __construct(AggregateInterface $aggregate) {
    $this->setAggregate($aggregate);
    parent::__construct(new \RecursiveArrayIterator($this->aggregate->getInput()));
  }

  /**
   * {@inheritDoc}
   *
   * @see RecursiveFilterIterator::accept()
   */
  public function accept() {
    // TODO: $accept is FALSE only if $this->current() is NULL ?
    $accept = (bool)$this->current();
    return $accept;
  }
}
