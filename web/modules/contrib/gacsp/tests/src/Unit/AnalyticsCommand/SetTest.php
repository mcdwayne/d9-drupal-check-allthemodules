<?php

namespace Drupal\Tests\gacsp\Unit\AnalyticsCommand;

use Drupal\gacsp\AnalyticsCommand\Set;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\gacsp\AnalyticsCommand\Set
 * @group gacsp
 */
class SetTest extends UnitTestCase {

  /**
   * Test the default priority.
   */
  public function testDefaultPriority() {
    $command = new Set('key', 'value');

    $this->assertEquals(100, $command->getPriority());
  }

  /**
   * Test the command array for a basic command without additional options.
   */
  public function testBasicSettingCommands() {
    $command = new Set('key', 'value');

    $this->assertEquals([['set', 'key', 'value']], $command->getSettingCommands());
  }

  /**
   * Test the command array when values are provided in fieldsObject.
   */
  public function testWithFieldsObjectSettingCommmands() {
    $command = new Set('key', 'value', ['field1' => 'value1']);

    $this->assertEquals([['set', 'key', 'value', ['field1' => 'value1']]], $command->getSettingCommands());
  }

  /**
   * Test the command when a tracker name is provided.
   */
  public function testWithTrackerNameSettingCommands() {
    $command = new Set('key', 'value', [], 'tracker');

    $this->assertEquals([['tracker.set', 'key', 'value']], $command->getSettingCommands());
  }

}
