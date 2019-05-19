<?php

namespace Drupal\Tests\time_field\Unit;

use Drupal\time_field\Time;
use PHPUnit\Framework\TestCase;

/**
 * Tests time.
 *
 * @group time_field
 */
class TimeTest extends TestCase {

  /**
   * Tests Time class creation.
   */
  public function testCreationWithInvalidArguments() {
    $this->expectException(\InvalidArgumentException::class);
    new Time(50);
  }

  /**
   * Test it can be created by html5 string.
   */
  public function testItCanBeCreatedByHtml5String() {
    $time = Time::createFromHtml5Format('13:40:30');
    $this->assertEquals('13', $time->getHour());
    $this->assertEquals('40', $time->getMinute());
    $this->assertEquals('30', $time->getSecond());

    $time = Time::createFromHtml5Format('14:50');
    $this->assertEquals('14', $time->getHour());
    $this->assertEquals('50', $time->getMinute());
    $this->assertEquals('0', $time->getSecond());
  }

  /**
   * Test time can attach to specific date.
   */
  public function testTimeOnDate() {
    $time = new Time(13, 40, 30);
    $dateTime = $time->on(new \DateTime());
    $this->assertEquals('13:40:30', $dateTime->format('H:i:s'));
  }

  /**
   * Test it can be created from day timestamp.
   */
  public function testItCanBeCreatedFromDayTimestamp() {
    $time = Time::createFromTimestamp(3700);
    $this->assertEquals('01:01:40', $time->format('H:i:s'));
  }

  /**
   * Test it formats for widgets in expected format.
   */
  public function testItFormatsForWidgetsInExpectedFormat() {
    $time = new Time(13, 40, 30);
    $this->assertEquals('13:40:30', $time->formatForWidget());
  }

}
