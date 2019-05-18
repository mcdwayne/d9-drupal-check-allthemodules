<?php

namespace Drupal\Tests\ga\Unit\AnalyticsCommand;

use Drupal\ga\AnalyticsCommand\Pageview;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ga\AnalyticsCommand\Pageview
 * @group ga
 */
class PageviewTest extends UnitTestCase {

  /**
   * Test the default priority.
   */
  public function testDefaultPriority() {
    $command = new Pageview();

    $this->assertEquals(-5, $command->getPriority());
  }

  /**
   * Test the command array for a basic command without additional options.
   */
  public function testBasicSettingCommands() {
    $command = new Pageview();

    $this->assertEquals([['send', 'pageview']], $command->getSettingCommands());
  }

  /**
   * Test the command array when values are provided in fieldsObject.
   */
  public function testWithFieldsObjectSettingCommmands() {
    $command = new Pageview(['field1' => 'value1']);

    $this->assertEquals([['send', 'pageview', ['field1' => 'value1']]], $command->getSettingCommands());
  }

  /**
   * Test the command when a tracker name is provided.
   */
  public function testWithTrackerNameSettingCommands() {
    $command = new Pageview([], 'tracker');

    $this->assertEquals([['tracker.send', 'pageview']], $command->getSettingCommands());
  }

}
