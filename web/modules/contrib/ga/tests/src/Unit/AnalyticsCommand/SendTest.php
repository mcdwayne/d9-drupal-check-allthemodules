<?php

namespace Drupal\Tests\ga\Unit\AnalyticsCommand;

use Drupal\ga\AnalyticsCommand\Send;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ga\AnalyticsCommand\Send
 * @group ga
 */
class SendTest extends UnitTestCase {

  /**
   * Test the default priority.
   */
  public function testDefaultPriority() {
    $command = new Send('pageview');

    $this->assertEquals(-10, $command->getPriority());
  }

  /**
   * Test the command array for a basic command without additional options.
   */
  public function testBasicSettingCommands() {
    $command = new Send('pageview');

    $this->assertEquals([['send', 'pageview']], $command->getSettingCommands());
  }

  /**
   * Test specifying an invalid hit type.
   *
   * @expectedException \InvalidArgumentException
   *
   * @expectedExceptionMessage Invalid hit type specified.
   */
  public function testInvalidHitType() {
    $command = new Send('badtype');
  }

  /**
   * Test the command array when values are provided in fieldsObject.
   */
  public function testWithFieldsObjectSettingCommmands() {
    $command = new Send('pageview', ['field1' => 'value1']);

    $this->assertEquals([['send', 'pageview', ['field1' => 'value1']]], $command->getSettingCommands());
  }

  /**
   * Test the command when a tracker name is provided.
   */
  public function testWithTrackerNameSettingCommands() {
    $command = new Send('pageview', [], 'tracker');

    $this->assertEquals([['tracker.send', 'pageview']], $command->getSettingCommands());
  }

}
