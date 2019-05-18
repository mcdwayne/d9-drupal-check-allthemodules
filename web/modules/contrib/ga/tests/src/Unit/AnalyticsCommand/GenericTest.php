<?php

namespace Drupal\Tests\ga\Unit\AnalyticsCommand;

use Drupal\ga\AnalyticsCommand\Generic;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ga\AnalyticsCommand\Generic
 * @group ga
 */
class GenericTest extends UnitTestCase {

  /**
   * Test the default priority.
   */
  public function testDefaultPriority() {
    $command = new Generic('commandName');

    $this->assertEquals(0, $command->getPriority());
  }

  /**
   * Test the command array for a basic command without additional options.
   */
  public function testBasicSettingCommands() {
    $command = new Generic('commandName');

    $this->assertEquals([['commandName']], $command->getSettingCommands());
  }

  /**
   * Test the command array when values are provided in fieldsObject.
   */
  public function testWithFieldsObjectSettingCommmands() {
    $command = new Generic('commandName', ['field1' => 'value1']);

    $this->assertEquals([['commandName', ['field1' => 'value1']]], $command->getSettingCommands());
  }

  /**
   * Test the command when a tracker name is provided.
   */
  public function testWithTrackerNameSettingCommands() {
    $command = new Generic('commandName', [], 'tracker');

    $this->assertEquals([['tracker.commandName']], $command->getSettingCommands());
  }

}
