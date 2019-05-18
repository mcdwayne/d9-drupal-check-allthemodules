<?php

namespace Drupal\Tests\contacts_events\Unit;

use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Component\Datetime\Time;
use Drupal\contacts_events\Cron\CronInterface;
use Drupal\contacts_events\Cron\CronTrait;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Test the cron trait correct controls the scheduling.
 *
 * @coversDefaultClass \Drupal\contacts_events\Cron\CronTrait
 */
class CronTraitTest extends UnitTestCase {

  /**
   * Test the constructor error checking on the last run state constant.
   *
   * @dataProvider dataStateKeyCheck
   *
   * @covers ::__construct
   */
  public function testStateKeyCheck($key_set) {
    $state = $this->prophesize(StateInterface::class)->reveal();
    $time = $this->prophesize(Time::class)->reveal();

    if ($key_set) {
      // @codingStandardsIgnoreStart
      $class = new class($state, $time) implements CronInterface  {
        use CronTrait;
        const STATE_LAST_RUN = 'state.key';
        protected function doInvoke() {}
      };
      // @codingStandardsIgnoreEnd
      $this->assertInstanceOf(CronInterface::class, $class);
    }
    else {
      $this->setExpectedException(\Exception::class, 'The run time state key must be set.');
      // @codingStandardsIgnoreStart
      new class($state, $time) {
        use CronTrait;
        protected function doInvoke() {}
      };
      // @codingStandardsIgnoreEnd
    }
  }

  /**
   * Data provider for testStateKeyCheck.
   */
  public function dataStateKeyCheck() {
    $data['set'] = [TRUE];
    $data['not-set'] = [FALSE];
    return $data;
  }

  /**
   * Test the constructor error checking on the last run state constant.
   *
   * @param array $arguments
   *   An array of additional constructor arguments, or NULL if there are none.
   *
   * @dataProvider dataStateKeyCheck
   *
   * @covers ::__construct
   */
  public function testInitServices(array ...$arguments) {
    $state = $this->prophesize(StateInterface::class)->reveal();
    $time = $this->prophesize(Time::class)->reveal();

    // @codingStandardsIgnoreStart
    $class = new class($state, $time, ...$arguments) implements CronInterface {
      use CronTrait;
      const STATE_LAST_RUN = 'state.key';
      public $additionalServices;
      protected function doInvoke() {}
      protected function initServices(...$arguments) {
        $this->additionalServices = $arguments;
      }
    };
    // @codingStandardsIgnoreEnd

    $this->assertSame($arguments, $class->additionalServices);
  }

  /**
   * Data provider for testInitServices.
   */
  public function dataInitServices() {
    $data['no-additional'] = [];
    $data['one-additional'] = [
      $this->prophesize(EntityTypeManagerInterface::class)->reveal(),
    ];
    $data['two-additional'] = [
      $this->prophesize(EntityTypeManagerInterface::class)->reveal(),
      $this->prophesize(EntityFieldManagerInterface::class)->reveal(),
    ];
    return $data;
  }

  /**
   * Test retrieving the current time.
   *
   * @covers ::getCurrentTime
   */
  public function testGetCurrentTime() {
    $state = $this->prophesize(StateInterface::class);
    $time = $this->prophesize(Time::class);
    $time->getCurrentTime()
      ->shouldBeCalledTimes(1)
      ->willReturn(946684800);

    // @codingStandardsIgnoreStart
    $class = new class($state->reveal(), $time->reveal()) implements CronInterface  {
      use CronTrait;
      const STATE_LAST_RUN = 'state.key';
      protected function doInvoke() {}
    };
    // @codingStandardsIgnoreEnd

    $method = new \ReflectionMethod($class, 'getCurrentTime');
    $method->setAccessible(TRUE);
    $result = $method->invoke($class);

    $this->assertInstanceOf(DateTimePlus::class, $result);
    $this->assertSame(946684800, $result->getTimestamp());
  }

  /**
   * Test retrieving the last run time.
   *
   * @param int|null $last_run
   *   The last run time stored in state.
   *
   * @dataProvider dataGetLastRunTime
   *
   * @covers ::getLastRunTime
   */
  public function testGetLastRunTime($last_run) {
    $state = $this->prophesize(StateInterface::class);
    $state->get('state.key')
      ->shouldBeCalledTimes(1)
      ->willReturn($last_run);
    $time = $this->prophesize(Time::class);

    // @codingStandardsIgnoreStart
    $class = new class($state->reveal(), $time->reveal()) implements CronInterface  {
      use CronTrait;
      const STATE_LAST_RUN = 'state.key';
      protected function doInvoke() {}
    };
    // @codingStandardsIgnoreEnd

    $method = new \ReflectionMethod($class, 'getLastRunTime');
    $method->setAccessible(TRUE);
    $result = $method->invoke($class);

    if ($last_run) {
      $this->assertInstanceOf(DateTimePlus::class, $result);
      $this->assertSame(946684800, $result->getTimestamp());
    }
    else {
      $this->assertNull($result);
    }
  }

  /**
   * Data provider for testGetLastRunTime.
   */
  public function dataGetLastRunTime() {
    $data['never-run'] = [NULL];
    $data['run'] = [946684800];
    return $data;
  }

  /**
   * Test setting the last run time.
   *
   * @covers ::setLastRunTime
   */
  public function testSetLastRunTime() {
    $state = $this->prophesize(StateInterface::class);
    $state->set('state.key', 946684800)
      ->shouldBeCalledTimes(1);
    $time = $this->prophesize(Time::class);
    $time->getCurrentTime()
      ->shouldBeCalledTimes(1)
      ->willReturn(946684800);

    // @codingStandardsIgnoreStart
    $class = new class($state->reveal(), $time->reveal()) implements CronInterface  {
      use CronTrait;
      const STATE_LAST_RUN = 'state.key';
      protected function doInvoke() {}
    };
    // @codingStandardsIgnoreEnd

    $method = new \ReflectionMethod($class, 'setLastRunTime');
    $method->setAccessible(TRUE);
    $method->invoke($class);
  }

  /**
   * Test figuring out the last run time.
   *
   * @param int $now
   *   The current time.
   * @param int|null $last_run
   *   The last run state value.
   * @param string $run_interval
   *   The CronTrait::$runInterval setting.
   * @param string|null $after_format
   *   The CronTrait::$runAfterFormat setting.
   * @param string|null $after_time
   *   The CronTrait::$runAfterTime setting.
   * @param bool|string $expected
   *   The result from the call or a string for an exception.
   *
   * @dataProvider dataScheduledToRun
   *
   * @covers ::scheduledToRun
   */
  public function testScheduledToRun($now, $last_run, $run_interval, $after_format, $after_time, $expected) {
    $state = $this->prophesize(StateInterface::class);
    $state->get('state.key')
      ->shouldBeCalledTimes(1)
      ->willReturn($last_run);
    $time = $this->prophesize(Time::class);
    $time->getCurrentTime()
      ->shouldBeCalledTimes(1)
      ->willReturn($now);

    // @codingStandardsIgnoreStart
    $class = new class($state->reveal(), $time->reveal()) implements CronInterface  {
      use CronTrait;
      const STATE_LAST_RUN = 'state.key';
      protected function doInvoke() {}
      public function setSettings($interval, $after_format, $after_time) {
        $this->runInterval = $interval;
        $this->runAfterFormat = $after_format;
        $this->runAfterTime = $after_time;
      }
    };
    // @codingStandardsIgnoreEnd

    $class->setSettings($run_interval, $after_format, $after_time);

    if (is_string($expected)) {
      $this->setExpectedException(\Exception::class, $expected);
    }
    $this->assertSame($expected, $class->scheduledToRun());
  }

  /**
   * Data provider for testScheduledToRun.
   */
  public function dataScheduledToRun() {
    $data['never-run:anytime'] = [
      strtotime('2000-01-01 12:00:00'),
      NULL,
      'H',
      NULL,
      NULL,
      TRUE,
    ];

    $data['never-run:invalid-interval:anytime'] = [
      strtotime('2000-01-01 12:00:00'),
      NULL,
      'f',
      NULL,
      NULL,
      'Invalid run interval.',
    ];

    $data['never-run:daily:anytime'] = [
      strtotime('2000-01-01 12:00:00'),
      NULL,
      'd',
      NULL,
      NULL,
      TRUE,
    ];

    $data['run-yesterday:daily:anytime'] = [
      strtotime('2000-01-02 12:00:00'),
      strtotime('2000-01-01 12:00:00'),
      'd',
      NULL,
      NULL,
      TRUE,
    ];

    $data['run-earlier-today:daily:anytime'] = [
      strtotime('2000-01-01 06:00:00'),
      strtotime('2000-01-01 12:00:00'),
      'd',
      NULL,
      NULL,
      FALSE,
    ];

    $data['run-later-today:daily:anytime'] = [
      strtotime('2000-01-01 18:00:00'),
      strtotime('2000-01-01 12:00:00'),
      'd',
      NULL,
      NULL,
      FALSE,
    ];

    $data['run-tomorrow:daily:anytime'] = [
      strtotime('2000-01-01 12:00:00'),
      strtotime('2000-01-02 12:00:00'),
      'd',
      NULL,
      NULL,
      FALSE,
    ];

    return $data;
  }

}
