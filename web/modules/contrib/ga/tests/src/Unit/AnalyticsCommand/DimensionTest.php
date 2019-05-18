<?php

namespace Drupal\Tests\ga\Unit\AnalyticsCommand;

use Drupal\ga\AnalyticsCommand\Dimension;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ga\AnalyticsCommand\Dimension
 * @group ga
 */
class DimensionTest extends UnitTestCase {

  /**
   * Test a valid index and value.
   */
  public function testIndexValue() {
    $command = new Dimension(42, 'value');

    $this->assertEquals([['set', 'dimension42', 'value']], $command->getSettingCommands());
  }

  /**
   * Test a valid index of type string and value.
   */
  public function testIndexStringValue() {
    $command = new Dimension('42', 'value');

    $this->assertEquals([['set', 'dimension42', 'value']], $command->getSettingCommands());
  }

  /**
   * Test an invalid index of type string.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testStringIndex() {
    new Dimension('index', 'value');
  }

  /**
   * Test an invalid index of type float.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testFloatIndex() {
    new Dimension(4.2, 'value');
  }

  /**
   * Test an index greater than the valid range.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testOutOfBoundsIndex() {
    new Dimension(420, 'value');
  }

}
