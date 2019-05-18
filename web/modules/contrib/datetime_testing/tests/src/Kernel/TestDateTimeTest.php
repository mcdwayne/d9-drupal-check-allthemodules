<?php

namespace Drupal\Tests\datetime_testing\Kernel;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\datetime_testing\Kernel\TestTestDateTime;

/**
 * @coversDefaultClass \Drupal\datetime_testing\TestDateTime
 * @group datetime_testing
 */
class TestDateTimeTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'datetime_testing',
  ];

  /**
   * Test construction of TestDateTime objects.
   *
   * @param string $timeString
   *   A string to be interpreted as a datetime.
   * @param string $timezone
   *   The name of the timezone to use in interpreting dates.
   * @param int $currentTime
   *   The current system time, normally from \Drupal::time()->getCurrentTime.
   * @param int $expected
   *   The expected datetime as a unix timestamp.
   *
   * @dataProvider providerTestConstruct
   */
  public function testConstruct($timeString, $timezone, $currentTime, $expected) {
    $settings = [
      'current_time' => $currentTime,
    ];
    $actual = new TestTestDatetime($timeString, NULL, $settings, $timezone);
    $actualStamp = $actual->getTimestamp();

    $expectedMessage = $this->stampToRead($expected);
    $actualMessage = $this->stampToRead($actualStamp);
    $currentMessage = $this->stampToRead($currentTime);
    $message = "Given the current time '$currentMessage' and timezone '$timezone' the string '$timeString' should be understood as '$expectedMessage' not '$actualMessage'";

    $this->assertEquals($actualStamp, $expected, $message);
  }

  /**
   * Converts a timestamp into a human readable datetime in UTC.
   *
   * @param int $timestamp
   *   A unix timestamp.
   */
  protected function stampToRead($timestamp) {
    $date = DateTimePlus::createFromTimestamp($timestamp, 'UTC');
    return $date->format('Y-m-d H:i:s e');
  }

  /**
   * Converts a human readable datetime in UTC into a timestamp.
   *
   * @param string $timeString
   *   A UTC datetime in the 'Y-m-d H:i:s'.
   */
  protected function readToStamp($timeString) {
    $date = DateTimePlus::createFromFormat('Y-m-d H:i:s', $timeString, 'UTC');
    return $date->getTimestamp();
  }

  /**
   * Provides data for date construction tests.
   *
   * @return array
   *   An array of arrays, each containing the input parameters for
   *   TestDateTimeTest::testConstruct().
   *
   * @see TestDateTimeTest::testConstruct()
   */
  public function providerTestConstruct() {
    return [
      // Absolute dates, site timezone UTC.
      // Fully specified.
      [
        'timeString' => '2018-03-13 17:12:23',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2018-03-13 17:12:23'),
      ],
      // Zero seconds specified.
      [
        'timeString' => '2018-03-13 17:12:00',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:04'),
        'expected' => $this->readToStamp('2018-03-13 17:12:00'),
      ],
      // Seconds not specified, should assume zero.
      [
        'timeString' => '2018-03-13 17:12',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2018-03-13 17:12:00'),
      ],
      // 12 hour clock.
      [
        'timeString' => '2018-03-13 5:12:23pm',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2018-03-13 17:12:23'),
      ],
      // 12 hour clock, seconds not specified.
      [
        'timeString' => '2018-03-13 5:12pm',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2018-03-13 17:12:00'),
      ],
      // 12 hour clock, zero minutes specified.
      [
        'timeString' => '2018-03-13 5:00pm',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2018-03-13 17:00:00'),
      ],
      // Minutes not specified, should assume zero.
      [
        'timeString' => '2018-03-13 5pm',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2018-03-13 17:00:00'),
      ],
      // Time not specified, should be midnight.
      [
        'timeString' => '2018-03-13',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2018-03-13 00:00:00'),
      ],
      // Asia/Dhaka is UTC +6 all year round.
      // Timezone specified, should override default.
      [
        'timeString' => '2018-03-13 17:00 Asia/Dhaka',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2018-03-13 11:00:00'),
      ],
      // Timezone specified but time not.
      [
        'timeString' => '2018-03-13 Asia/Dhaka',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2018-03-12 18:00:00'),
      ],
      // Relative dates.
      // Relative date, absolute time.
      [
        'timeString' => 'tomorrow 5pm',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2017-03-13 17:00:00'),
      ],
      // Midnight specified.
      [
        'timeString' => 'tomorrow 00:00',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2017-03-13 00:00:00'),
      ],
      // Relative date, time not specified.
      [
        'timeString' => 'tomorrow',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2017-03-13 00:00:00'),
      ],
      // Relative time, date not specified.
      [
        'timeString' => '+1 hour',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2017-03-12 04:04:05'),
      ],
      // Relative time when current time after midday.
      [
        'timeString' => '+2 hours',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 13:04:05'),
        'expected' => $this->readToStamp('2017-03-12 15:04:05'),
      ],
      // Relative date, time not specified, relative time.
      [
        'timeString' => 'tomorrow + 1 hour',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2017-03-13 01:00:00'),
      ],
      // Absolute date, time not specified, relative time.
      [
        'timeString' => '2018-03-13 + 1 hour',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2018-03-13 01:00:00'),
      ],
      // Past relative date.
      [
        'timeString' => 'yesterday 5pm',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2017-03-11 17:00:00'),
      ],
      // 2017-03-12 is a Sunday.
      // Weekday with time not specified.
      [
        'timeString' => 'next tuesday',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2017-03-14 00:00:00'),
      ],
      // Weekday with time specified.
      [
        'timeString' => 'next tuesday 5pm',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2017-03-14 17:00:00'),
      ],
      // Past weekday with time specified.
      [
        'timeString' => 'last tuesday 5pm',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2017-03-07 17:00:00'),
      ],
      // Relative time changes the weekday.
      [
        'timeString' => 'next Tuesday +25 hours',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2017-03-15 01:00:00'),
      ],
      // Relative date, time and timezone specified.
      [
        'timeString' => 'tomorrow 5pm Asia/Dhaka',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2017-03-13 11:00:00'),
      ],
      // Site timezone Asia/Dhaka
      // Absolute date and time specified.
      [
        'timeString' => '2017-03-13 17:00',
        'timezone' => 'Asia/Dhaka',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2017-03-13 11:00:00'),
      ],
      // Relative date, time specified on same date UTC.
      [
        'timeString' => 'tomorrow 17:00',
        'timezone' => 'Asia/Dhaka',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2017-03-13 11:00:00'),
      ],
      // Date specified but not time.
      [
        'timeString' => '2017-03-13',
        'timezone' => 'Asia/Dhaka',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2017-03-12 18:00:00'),
      ],
      // Date specified but time specified in a potentially different day.
      [
        'timeString' => '2017-03-13 4am',
        'timezone' => 'Asia/Dhaka',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2017-03-12 22:00:00'),
      ],
      // Relative date with time specified in a potentially different day.
      [
        'timeString' => 'tomorrow 4am',
        'timezone' => 'Asia/Dhaka',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2017-03-12 22:00:00'),
      ],
      // Relative date with time specified late in the day.
      [
        'timeString' => 'tomorrow 22:00',
        'timezone' => 'Asia/Dhaka',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2017-03-13 16:00:00'),
      ],
      // Current time late in day.
      [
        'timeString' => 'tomorrow 08:00',
        'timezone' => 'Asia/Dhaka',
        'currentTime' => $this->readToStamp('2017-03-12 23:04:05'),
        'expected' => $this->readToStamp('2017-03-14 02:00:00'),
      ],
      // Relative date and time.
      [
        'timeString' => 'tomorrow +1 hour',
        'timezone' => 'Asia/Dhaka',
        'currentTime' => $this->readToStamp('2017-03-12 23:04:05'),
        'expected' => $this->readToStamp('2017-03-13 19:00:00'),
      ],
      // Relative time only.
      [
        'timeString' => '+1 hour',
        'timezone' => 'Asia/Dhaka',
        'currentTime' => $this->readToStamp('2017-03-12 23:04:05'),
        'expected' => $this->readToStamp('2017-03-13 00:04:05'),
      ],
      // An edge case, but keeps our logic tight.
      [
        'timeString' => 'now Asia/Dhaka',
        'timezone' => 'UTC',
        'currentTime' => $this->readToStamp('2017-03-12 03:04:05'),
        'expected' => $this->readToStamp('2017-03-12 03:04:05'),
      ],
    ];
  }

}
