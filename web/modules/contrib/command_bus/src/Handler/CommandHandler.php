<?php

namespace Drupal\command_bus\Handler;

use Drupal\command_bus\Command\CommandInterface;
use Drupal\command_bus\Validator\Violations;

/**
 * Class CommandHandler.
 *
 * @package Drupal\command_bus\Command
 */
abstract class CommandHandler implements CommandHandlerInterface {

  /**
   * The attached command.
   *
   * @var \Drupal\command_bus\Command\CommandInterface
   */
  private $command;

  /**
   * Returns the attached command.
   *
   * @return \Drupal\command_bus\Command\CommandInterface
   *   The attached command.
   */
  public function getCommand() {
    return $this->command;
  }

  /**
   * Sets the attached command.
   *
   * @param \Drupal\command_bus\Command\CommandInterface $command
   *   The attached command.
   */
  public function setCommand(CommandInterface $command) {
    $this->command = $command;
  }

  /**
   * Handles the command.
   */
  abstract public function handle();

  /**
   * Rollback the command if post validation fails.
   *
   * @param \Drupal\command_bus\Validator\Violations $violations
   *   The violations.
   */
  abstract public function rollback(Violations $violations);

}
