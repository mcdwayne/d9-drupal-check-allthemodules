<?php

namespace Drupal\Tests\protect_before_launch\Unit\Command;

use Drupal\protect_before_launch\Command\UsernameCommand;
use Drupal\protect_before_launch\Configuration;
use Drupal\Tests\protect_before_launch\Unit\UnitTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UsernameCommandTest.
 *
 * @group tests
 * @package Drupal\Tests\protect_before_launch\Unit\Command
 */
class UsernameCommandTest extends UnitTestCase {

  /**
   * Test set username.
   */
  public function testSetUsername() {
    $commandTester = new CommandTester($this->getCommand());
    $commandTester->execute(
      [
        'username' => 'updated_username',
      ]
    );

    $this->assertContains('commands.protect_before_launch.username.messages.success', $commandTester->getDisplay());
  }

  /**
   * Test set username interactively.
   */
  public function testSetUsernameInteractive() {
    $this->verifySymfonyConsoleInputsSupport();

    $commandTester = new CommandTester($this->getCommand());
    $commandTester->setInputs(['updated_username']);
    $commandTester->execute([]);

    $this->assertContains('commands.protect_before_launch.username.messages.question', $commandTester->getDisplay());
    $this->assertContains('commands.protect_before_launch.username.messages.success', $commandTester->getDisplay());
  }

  /**
   * Get command to test.
   *
   * @return \Drupal\protect_before_launch\Command\UsernameCommand
   *   Command.
   */
  private function getCommand() {
    $configuration = $this->createMock(Configuration::class);
    $configuration->expects($this->exactly(1))->method('setUsername')->willReturn(TRUE);

    return new UsernameCommand($configuration);
  }

}
