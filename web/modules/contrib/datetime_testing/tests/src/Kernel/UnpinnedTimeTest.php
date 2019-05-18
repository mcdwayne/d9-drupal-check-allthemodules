<?php

namespace Drupal\Tests\datetime_testing\Kernel;

use Drupal\Component\Datetime\Time;
use Drupal\KernelTests\KernelTestBase;
use Drupal\datetime_testing\TestTime;

/**
 * Tests datetime_testing's time without overriding php's native time functions.
 *
 * Test the manipulation of the time while it is flowing, and its freezing or
 * unfreezing.
 *
 * @coversDefaultClass \Drupal\datetime_testing\TestTime
 * @group datetime_testing
 */
class UnpinnedTimeTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'datetime_testing',
  ];

  /**
   * The normal time class.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $normalTime;

  /**
   * Our testing time class.
   *
   * @var \Drupal\datetime_testing\TestTimeInterface
   */
  protected $testTime;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->normalTime = new Time(\Drupal::service('request_stack'));
    $this->testTime = new TestTime($this->normalTime, \Drupal::state());
    $this->testTime->resetTime();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    $this->testTime->resetTime();
    parent::tearDown();
  }

  /**
   * Tests the getCurrentMicroTime method.
   *
   * @covers ::getCurrentMicroTime
   */
  public function testGetCurrentMicroTime() {
    // Should be close to the true time, allowing for a little processing time.
    $this->assertEquals($this->testTime->getCurrentMicroTime(), $this->normalTime->getCurrentMicroTime(), '', 0.1);
  }

  /**
   * Tests freezing time and then setting it.
   */
  public function testFreezeSettingTime() {
    $mockTime = 100;
    $sleep = 2;
    $this->testTime->freezeTime();
    $this->testTime->setTime($mockTime);
    sleep($sleep);
    $this->assertEquals($mockTime, $this->testTime->getCurrentTime());

    $this->testTime->unfreezeTime();
    sleep($sleep);
    $this->assertEquals($mockTime + $sleep, $this->testTime->getCurrentTime());
  }

  /**
   * Tests freezing time and then shifting it.
   */
  public function testFreezeShiftingTime() {
    $shift = 20;
    $sleep = 2;
    $stopTime = $this->normalTime->getCurrentTime();
    $this->testTime->freezeTime();
    $this->testTime->setTime("$shift seconds");
    sleep($sleep);
    $this->assertEquals($stopTime + $shift, $this->testTime->getCurrentTime());

    $this->testTime->unfreezeTime();
    sleep($sleep);
    $this->assertEquals($stopTime + $shift + $sleep, $this->testTime->getCurrentTime());

    $this->testTime->freezeTime();
    sleep($sleep);
    $this->assertEquals($stopTime + $shift + $sleep, $this->testTime->getCurrentTime());
  }

  /**
   * Tests setting time then shifting it, all without freezing it.
   */
  public function testSettingShiftingTime() {
    $mockTime = 100;
    $sleep = 2;
    $shift = 20;

    $this->testTime->setTime($mockTime);
    $this->assertEquals($mockTime, $this->testTime->getCurrentTime());

    sleep($sleep);
    $this->assertEquals($mockTime + $sleep, $this->testTime->getCurrentTime());

    $this->testTime->setTime("$shift seconds");
    $this->assertEquals($mockTime + $sleep + $shift, $this->testTime->getCurrentTime());

    sleep($sleep);
    $this->testTime->setTime("$shift seconds");
    $this->assertEquals($mockTime + $sleep + $shift + $sleep + $shift, $this->testTime->getCurrentTime(), '', 0.1);

    $this->testTime->freezeTime();
    sleep($sleep);
    $this->assertEquals($mockTime + $sleep + $shift + $sleep + $shift, $this->testTime->getCurrentTime());

    $this->testTime->unFreezeTime();
    sleep($sleep);
    $this->assertEquals($mockTime + $sleep + $shift + $sleep + $shift + $sleep, $this->testTime->getCurrentTime());

    $this->testTime->setTime($mockTime);
    $this->assertEquals($mockTime, $this->testTime->getCurrentTime());

    sleep($sleep);
    $this->assertEquals($mockTime + $sleep, $this->testTime->getCurrentTime());

  }

}
