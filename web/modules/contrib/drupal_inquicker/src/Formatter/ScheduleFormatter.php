<?php

namespace Drupal\drupal_inquicker\Formatter;

use Drupal\drupal_inquicker\Schedule\Schedule;
use Drupal\drupal_inquicker\traits\Singleton;

/**
 * Formats a Schedule as an array.
 */
class ScheduleFormatter extends Formatter {

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
    return $data->data();
  }

  /**
   * {@inheritdoc}
   */
  public function validateSource($data) {
    $this->validateClass($data, Schedule::class);
  }

}
