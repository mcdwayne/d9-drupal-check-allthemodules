<?php

namespace Drupal\oh;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for opening hours service.
 */
interface OhOpeningHoursInterface {

  /**
   * Get hours for a location between a range.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The location.
   * @param \Drupal\oh\OhDateRange $range
   *   Get occurrences between a range of dates.
   *
   * @return \Drupal\oh\OhOccurrence[]
   *   An unordered array of occurrences.
   */
  public function getOccurrences(EntityInterface $entity, OhDateRange $range): array;

  /**
   * Get regular hours for a location between a range.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The location.
   * @param \Drupal\oh\OhDateRange $range
   *   Get regular hours between a range of dates.
   *
   * @return \Drupal\oh\OhOccurrence[]
   *   An unordered array of occurrences.
   */
  public function getRegularHours(EntityInterface $entity, OhDateRange $range): array;

  /**
   * Get exceptions for a location between a range.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The location.
   * @param \Drupal\oh\OhDateRange $range
   *   Get exceptions between a range of dates.
   *
   * @return \Drupal\oh\OhOccurrence[]
   *   An unordered array of occurrences.
   */
  public function getExceptions(EntityInterface $entity, OhDateRange $range): array;

}
