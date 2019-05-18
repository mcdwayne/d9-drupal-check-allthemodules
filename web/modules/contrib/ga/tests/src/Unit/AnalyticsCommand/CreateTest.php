<?php

namespace Drupal\Tests\ga\Unit\AnalyticsCommand;

use Drupal\ga\AnalyticsCommand\Create;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ga\AnalyticsCommand\Create
 * @group ga
 */
class CreateTest extends UnitTestCase {

  /**
   * Test the default priority.
   */
  public function testDefaultPriority() {
    $command = new Create('UA-12345678-1');

    $this->assertEquals(300, $command->getPriority());
  }

  /**
   * Test the command array for a basic command without additional options.
   */
  public function testBasicSettingCommands() {
    $command = new Create('UA-12345678-1');

    $this->assertEquals([['create', 'UA-12345678-1', 'auto']], $command->getSettingCommands());
  }

  /**
   * Test the command array with cookie domain specified.
   */
  public function testCookieDomainSettingCommand() {
    $command = new Create('UA-12345678-1', '.example.com');

    $this->assertEquals([['create', 'UA-12345678-1', '.example.com']], $command->getSettingCommands());
  }

  /**
   * Test the command array when values are provided in fieldsObject.
   */
  public function testWithFieldsObjectSettingCommmands() {
    $command = new Create('UA-12345678-1', 'auto', NULL, ['field1' => 'value1']);

    $this->assertEquals(
      [['create', 'UA-12345678-1', 'auto', ['field1' => 'value1']]],
      $command->getSettingCommands()
    );
  }

  /**
   * Test the command when a tracker name is provided.
   */
  public function testWithTrackerNameSettingCommands() {
    $command = new Create('UA-12345678-1', 'auto', 'tracker', []);

    $this->assertEquals([['create', 'UA-12345678-1', 'auto', 'tracker']], $command->getSettingCommands());
  }

}
