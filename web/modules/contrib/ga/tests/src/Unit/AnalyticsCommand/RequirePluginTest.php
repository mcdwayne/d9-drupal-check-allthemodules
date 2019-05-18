<?php

namespace Drupal\Tests\ga\Unit\AnalyticsCommand;

use Drupal\ga\AnalyticsCommand\RequirePlugin;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ga\AnalyticsCommand\RequirePlugin
 * @group ga
 */
class RequirePluginTest extends UnitTestCase {

  /**
   * Test the default priority.
   */
  public function testDefaultPriority() {
    $command = new RequirePlugin('pluginName');

    $this->assertEquals(250, $command->getPriority());
  }

  /**
   * Test the command array for a basic command without additional options.
   */
  public function testBasicSettingCommands() {
    $command = new RequirePlugin('pluginName');

    $this->assertEquals([['require', 'pluginName']], $command->getSettingCommands());
  }

  /**
   * Test the command array when values are provided in fieldsObject.
   */
  public function testWithFieldsObjectSettingCommmands() {
    $command = new RequirePlugin('pluginName', ['field1' => 'value1']);

    $this->assertEquals([['require', 'pluginName', ['field1' => 'value1']]], $command->getSettingCommands());
  }

  /**
   * Test the command when a tracker name is provided.
   */
  public function testWithTrackerNameSettingCommands() {
    $command = new RequirePlugin('pluginName', [], 'tracker');

    $this->assertEquals([['tracker.require', 'pluginName']], $command->getSettingCommands());
  }

}
