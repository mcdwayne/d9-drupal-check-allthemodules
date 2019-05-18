<?php

namespace Drupal\loopit\Iterator;

use Drupal\loopit\Aggregate\AggregateInterface;


class AggregateIterator extends \RecursiveArrayIterator implements AggregateIteratorInterface {
  use AggregateIteratorTrait;

  public function __construct(AggregateInterface $aggregate) {
    $this->setAggregate($aggregate);
    parent::__construct($this->aggregate->getInput());
  }
}
