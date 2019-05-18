<?php

namespace Drupal\drupal_inquicker\Tests\Source;

use Drupal\drupal_inquicker\Formatter\ScheduleListFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Test ScheduleListFormatter.
 *
 * @group myproject
 */
class ScheduleListFormatterTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = $this->getMockBuilder(ScheduleListFormatter::class)
      // NULL = no methods are mocked; otherwise list the methods here.
      ->setMethods([])
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();

    $this->assertTrue(is_object($object));
  }

}
