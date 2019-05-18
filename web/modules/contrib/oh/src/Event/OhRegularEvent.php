<?php

namespace Drupal\oh\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\oh\OhDateRange;
use Drupal\oh\OhOccurrence;
use Symfony\Component\EventDispatcher\Event;

/**
 * Used to add regular hours between a date range.
 *
 * @see \Drupal\oh\Event\OhEvents::REGULAR
 */
class OhRegularEvent extends Event {

  /**
   * The location.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The regular hours for a location.
   *
   * @var \Drupal\oh\OhOccurrence[]
   */
  protected $regularHours = [];

  /**
   * The date range to get regular hours between.
   *
   * @var \Drupal\oh\OhDateRange
   */
  protected $range;

  /**
   * Construct a new OhRegularEvent.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The location.
   * @param \Drupal\oh\OhDateRange $range
   *   Get regular hours between a range of dates.
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
   * Get the date range to get regular hours between.
   *
   * @return \Drupal\oh\OhDateRange
   *   The date range to get regular hours between.
   */
  public function getRange(): OhDateRange {
    return $this->range;
  }

  /**
   * Add regular hours for a location.
   *
   * @param \Drupal\oh\OhOccurrence $occurrence
   *   Regular hours for a location.
   *
   * @return $this
   *   Return object for chaining.
   *
   * @throws \Exception
   *   If the regular hours is outside of the requested range.
   */
  public function addRegularHours(OhOccurrence $occurrence) {
    if ($this->range->isWithin($occurrence, TRUE)) {
      $this->regularHours[] = $occurrence;
    }
    return $this;
  }

  /**
   * Get the regular hours for a location.
   *
   * @return \Drupal\oh\OhOccurrence[]
   *   The regular hours for a location.
   */
  public function getRegularHours(): array {
    return $this->regularHours;
  }

}
