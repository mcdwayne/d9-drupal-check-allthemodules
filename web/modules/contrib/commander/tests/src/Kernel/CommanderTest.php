<?php

namespace Drupal\Tests\commander\Kernel;

use Drupal\commander_test\Commands\RegisterUser;
use Drupal\KernelTests\KernelTestBase;

/**
 * Class CommanderTest.
 *
 * @group commander
 */
class CommanderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'commander',
    'commander_test',
  ];

  /**
   * Ensures that the custom command is executed.
   */
  public function testCommandIsExecuted() {
    $registered = FALSE;
    $command = new RegisterUser($registered);

    $executedCommand = \Drupal::service('commander.command_bus')->execute($command);

    $this->assertSame(TRUE, $executedCommand->registered);
  }

}
