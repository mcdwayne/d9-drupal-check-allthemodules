<?php

namespace Drupal\Tests\cached_computed_field\Kernel;

use Drupal\Component\Datetime\Time;

/**
 * Provides a mock time service that can be used for testing.
 */
class MockTimeService extends Time {

  /**
   * The timestamp being represented by this object.
   *
   * @var int
   */
  protected $time;

  /**
   * Constructs a MockTimeService object.
   *
   * @param int $time
   *   The UNIX time stamp to set on the object.
   */
  public function __construct(int $time) {
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestTime() {
    return $this->time;
  }

}
