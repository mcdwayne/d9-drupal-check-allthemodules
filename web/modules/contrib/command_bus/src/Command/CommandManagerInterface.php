<?php

namespace Drupal\command_bus\Command;

/**
 * Interface CommandManager.
 *
 * @package Drupal\command_bus\Command
 */
interface CommandManagerInterface {

  /**
   * Runs pre validation checks before the command is run.
   *
   * @param \Drupal\command_bus\Command\CommandInterface $command
   *   The command.
   *
   * @return \Drupal\command_bus\Validator\Violations
   *   The violations.
   */
  public function preValidate(CommandInterface $command);

  /**
   * Runs post validation checks on the command result.
   *
   * @param mixed $result
   *   The command result.
   * @param \Drupal\command_bus\Command\CommandInterface $command
   *   The command.
   *
   * @return \Drupal\command_bus\Validator\Violations
   *   The violations.
   */
  public function postValidate($result, CommandInterface $command);

}
