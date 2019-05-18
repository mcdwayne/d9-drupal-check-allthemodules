<?php

namespace Drupal\drupal_inquicker\Formatter;

use Drupal\drupal_inquicker\Schedule\ScheduleCollection;
use Drupal\drupal_inquicker\traits\Singleton;

/**
 * Formats a ScheduleCollection as an array of ids.
 */
class ScheduleListFormatter extends Formatter {

  use Singleton;

  /**
   * {@inheritdoc}
   */
  public function catchError(\Throwable $t) {
    $this->watchdogThrowable($t);
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function formatValidatedSource($data) {
    $return['types'] = [];
    foreach ($data as $item) {
      $return['times'][] = $this->scheduleFormatter()->format($item);
      $return['types'] = array_merge($return['types'], $item->types());
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function validateSource($data) {
    $this->validateClass($data, ScheduleCollection::class);
    $data->validateMembers();
  }

}
