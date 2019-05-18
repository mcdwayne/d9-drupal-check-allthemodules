<?php

namespace Drupal\command_bus\Command;

use Drupal\command_bus\Validator\Violations;

/**
 * Class CommandManager.
 *
 * @package Drupal\command_bus\Command
 */
class CommandManager implements CommandManagerInterface {

  /**
   * Runs pre validation checks on the command.
   *
   * @param \Drupal\command_bus\Command\CommandInterface $command
   *   The command.
   *
   * @return \Drupal\command_bus\Validator\Violations
   *   The violations.
   */
  public function preValidate(CommandInterface $command) {
    $violations = new Violations();
    $validators = $command->getPreValidators();

    foreach ($validators as $validator) {
      $validator->validate($command, $violations);
    }

    return $violations;
  }

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
  public function postValidate($result, CommandInterface $command) {
    $violations = new Violations();
    $validators = $command->getPostValidators();

    foreach ($validators as $validator) {
      $validator->validate($result, $violations);
    }

    return $violations;
  }

}
