<?php

namespace Drupal\command_bus\Handler;

use Drupal\command_bus\Command\CommandInterface;
use Drupal\command_bus\Validator\Violations;

/**
 * Interface CommandHandlerInterface.
 *
 * @package Drupal\command_bus\Handler
 */
interface CommandHandlerInterface {

  /**
   * Sets the attached command.
   *
   * @param \Drupal\command_bus\Command\CommandInterface $command
   *   The attached command.
   */
  public function setCommand(CommandInterface $command);

  /**
   * Returns the attached command.
   *
   * @return \Drupal\command_bus\Command\CommandInterface
   *   The attached command.
   */
  public function getCommand();

  /**
   * Handles the command.
   */
  public function handle();

  /**
   * Rolls back the command.
   *
   * @param \Drupal\command_bus\Validator\Violations $violations
   *   The violations.
   */
  public function rollback(Violations $violations);

}
