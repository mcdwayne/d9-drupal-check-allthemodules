<?php

namespace Drupal\Tests\datetime_testing\Kernel;

use Drupal\datetime_testing\TestDateTime;

/**
 * A datetime object that allows overriding the default timezone.
 *
 * Intended for testing the TestDatetime class.
 */
class TestTestDateTime extends TestDateTime {

  /**
   * The current time.
   *
   * @var string
   */
  protected $fallbackTimezone;

  /**
   * {@inheritdoc}
   */
  public function __construct($time = 'now', $timezone = NULL, $settings = [], $fallbackTimezone = 'UTC') {
    $this->fallbackTimezone = $fallbackTimezone;
    // Instantiate the parent class.
    parent::__construct($time, $timezone, $settings);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareTimezone($time) {
    // Override the default timezone for testing purposes.
    if (empty($time) && !empty($this->fallbackTimezone)) {
      return new \DateTimeZone($this->fallbackTimezone);
    }
    else {
      return parent::prepareTimezone($time);
    }
  }

}
