<?php

namespace Drupal\loopit\Iterator;

use Drupal\loopit\Aggregate\AggregateInterface;

/**
 * @todo comments
 */
interface AggregateIteratorInterface extends \RecursiveIterator {

  public function setAggregate(AggregateInterface $aggregate);
}