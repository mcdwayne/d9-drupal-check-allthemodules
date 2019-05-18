<?php

namespace Drupal\Tests\ga\Unit\AnalyticsCommand;

use Drupal\ga\AnalyticsCommand\Metric;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ga\AnalyticsCommand\Metric
 * @group ga
 */
class MetricTest extends UnitTestCase {

  /**
   * Test a valid index and value.
   */
  public function testIndexValue() {
    $command = new Metric(42, 123);

    $this->assertEquals([['set', 'metric42', 123]], $command->getSettingCommands());
  }

  /**
   * Test a valid index and value of type string.
   *
   * Integer value should be cast.
   */
  public function testStringIndexIntStringValue() {
    $command = new Metric('42', '123');

    $this->assertEquals([['set', 'metric42', 123]], $command->getSettingCommands());
  }

  /**
   * Test a valid index and value of type string.
   *
   * Decimal value should be cast as float so decimal is retained.
   */
  public function testStringIndexFloatStringValue() {
    $command = new Metric('42', '123.45');

    $this->assertEquals([['set', 'metric42', 123.45]], $command->getSettingCommands());
  }

  /**
   * Test an invalid index of type string.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testStringIndex() {
    new Metric('index', 123);
  }

  /**
   * Test an invalid index of type float.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testFloatIndex() {
    new Metric(4.2, 123);
  }

  /**
   * Test an index greater than the valid range.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testOutOfBoundsIndex() {
    new Metric(420, 123);
  }

  /**
   * Test a string value.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testStringValue() {
    new Metric(42, 'value');
  }

}
