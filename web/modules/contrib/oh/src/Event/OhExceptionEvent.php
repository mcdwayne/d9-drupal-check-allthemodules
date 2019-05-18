<?php

namespace Drupal\oh\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\oh\OhDateRange;
use Drupal\oh\OhOccurrence;
use Symfony\Component\EventDispatcher\Event;

/**
 * Used to add exceptions between a date range.
 *
 * @see \Drupal\oh\Event\OhEvents::EXCEPTIONS
 */
class OhExceptionEvent extends Event {

  /**
   * The location.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The exceptions for a location.
   *
   * @var \Drupal\oh\OhOccurrence[]
   */
  protected $exceptions = [];

  /**
   * The date range to get exceptions between.
   *
   * @var \Drupal\oh\OhDateRange
   */
  protected $range;

  /**
   * Construct a new OhExceptionEvent.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The location.
   * @param \Drupal\oh\OhDateRange $range
   *   Get exceptions between a range of dates.
   */
  public function __construct(EntityInterface $entity, OhDateRange $range) {
    $this->entity = $entity;
    $this->range = $range;
  }

  /**
   * Get the location.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The location.
   */
  public function getEntity(): EntityInterface {
    return $this->entity;
  }

  /**
   * Get the date range to get exceptions between.
   *
   * @return \Drupal\oh\OhDateRange
   *   The date range to get exceptions between.
   */
  public function getRange(): OhDateRange {
    return $this->range;
  }

  /**
   * Add an exception for a location.
   *
   * @param \Drupal\oh\OhOccurrence $occurrence
   *   An exception for a location.
   *
   * @return $this
   *   Return object for chaining.
   *
   * @throws \Exception
   *   If the added exception is outside of the requested range.
   */
  public function addException(OhOccurrence $occurrence) {
    if ($this->range->isWithin($occurrence)) {
      $this->exceptions[] = $occurrence;
    }
    return $this;
  }

  /**
   * Get the exceptions for a location.
   *
   * @return \Drupal\oh\OhOccurrence[]
   *   The exceptions for a location.
   */
  public function getExceptions(): array {
    return $this->exceptions;
  }

}
