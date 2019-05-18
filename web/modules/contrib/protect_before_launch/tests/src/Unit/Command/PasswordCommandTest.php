<?php

namespace Drupal\Tests\protect_before_launch\Unit\Command;

use Drupal\protect_before_launch\Command\PasswordCommand;
use Drupal\protect_before_launch\Configuration;
use Drupal\Tests\protect_before_launch\Unit\UnitTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class PasswordCommandTest.
 *
 * @group tests
 * @package Drupal\Tests\protect_before_launch\Unit\Command
 */
class PasswordCommandTest extends UnitTestCase {

  /**
   * Test setting a password.
   */
  public function testSetPassword() {
    $commandTester = new CommandTester($this->getCommand());
    $commandTester->execute(
      [
        'password' => 'updated_password',
      ]
    );

    $this->assertContains('commands.protect_before_launch.password.messages.success', $commandTester->getDisplay());
  }

  /**
   * Test setting a password interactively.
   */
  public function testSetPasswordInteractive() {
    $this->verifySymfonyConsoleInputsSupport();

    $commandTester = new CommandTester($this->getCommand());
    $commandTester->setInputs(['updated_password']);
    $commandTester->execute([]);

    $this->assertContains('commands.protect_before_launch.password.messages.question', $commandTester->getDisplay());
    $this->assertContains('commands.protect_before_launch.password.messages.success', $commandTester->getDisplay());
  }

  /**
   * Get command to test.
   *
   * @return \Drupal\protect_before_launch\Command\PasswordCommand
   *   Command
   */
  private function getCommand() {
    $configuration = $this->createMock(Configuration::class);
    $configuration->expects($this->exactly(1))->method('setPassword')->willReturn(TRUE);

    return new PasswordCommand($configuration);
  }

}
