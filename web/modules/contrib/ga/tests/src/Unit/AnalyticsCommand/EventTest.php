<?php

namespace Drupal\Tests\ga\Unit\AnalyticsCommand;

use Drupal\ga\AnalyticsCommand\Event;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ga\AnalyticsCommand\Event
 * @group ga
 */
class EventTest extends UnitTestCase {

  /**
   * Test the default priority.
   */
  public function testDefaultPriority() {
    $command = new Event('category', 'action');

    $this->assertEquals(-10, $command->getPriority());
  }

  /**
   * Test the command array for a basic command without additional options.
   */
  public function testBasicSettingCommands() {
    $command = new Event('category', 'action');

    $this->assertEquals([['send', 'event', 'category', 'action']], $command->getSettingCommands());
  }

  /**
   * Test the command array when label is provided.
   */
  public function testWithLabelSettingCommands() {
    $command = new Event('category', 'action', 'label');

    $this->assertEquals([['send', 'event', 'category', 'action', 'label']], $command->getSettingCommands());
  }

  /**
   * Test the command array when label and value are provided.
   */
  public function testWithLabelAndValueSettingCommands() {
    $command = new Event('category', 'action', 'label', 1);

    $this->assertEquals([['send', 'event', 'category', 'action', 'label', 1]], $command->getSettingCommands());
  }

  /**
   * Test the command array when label and a zero value are provided.
   */
  public function testWithLabelAndZeroValueSettingCommands() {
    $command = new Event('category', 'action', 'label', 0);

    $this->assertEquals([['send', 'event', 'category', 'action', 'label', 0]], $command->getSettingCommands());
  }

  /**
   * Test the command array when value is provided, but not label.
   */
  public function testWithValueSettingCommands() {
    $command = new Event('category', 'action', NULL, 1);

    $this->assertEquals(
      [['send', 'event', 'category', 'action', ['eventValue' => 1]]],
      $command->getSettingCommands()
    );
  }

  /**
   * Test the command array when zero value is provided, but not label.
   */
  public function testWithZeroValueSettingCommands() {
    $command = new Event('category', 'action', NULL, 0);

    $this->assertEquals(
      [['send', 'event', 'category', 'action', ['eventValue' => 0]]],
      $command->getSettingCommands()
    );
  }

  /**
   * Test with a float event value.
   *
   * @expectedException \InvalidArgumentException
   *
   * @expectedExceptionMessage Event value must be a positive integer
   */
  public function testWithFloatValue() {
    $command = new Event('category', 'action', NULL, 1.5);
  }

  /**
   * Test with a negative integer event value.
   *
   * @expectedException \InvalidArgumentException
   *
   * @expectedExceptionMessage Event value must be a positive integer
   */
  public function testWithNegativeIntegerValue() {
    $command = new Event('category', 'action', NULL, -1);
  }

  /**
   * Test with a string event value.
   *
   * @expectedException \InvalidArgumentException
   *
   * @expectedExceptionMessage Event value must be a positive integer
   */
  public function testWithStringValue() {
    $command = new Event('category', 'action', NULL, '1');
  }

  /**
   * Test the command array when values are provided in fieldsObject.
   */
  public function testWithFieldsObjectSettingCommmands() {
    $command = new Event('category', 'action', NULL, NULL, ['field1' => 'value1']);

    $this->assertEquals(
      [['send', 'event', 'category', 'action', ['field1' => 'value1']]],
      $command->getSettingCommands()
    );
  }

  /**
   * Test the command when a tracker name is provided.
   */
  public function testWithTrackerNameSettingCommands() {
    $command = new Event('category', 'action', NULL, NULL, [], 'tracker');

    $this->assertEquals([['tracker.send', 'event', 'category', 'action']], $command->getSettingCommands());
  }

}
