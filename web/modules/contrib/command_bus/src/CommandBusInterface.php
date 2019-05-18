<?php

namespace Drupal\command_bus;

use Drupal\command_bus\Command\CommandInterface;

/**
 * Interface DefaultCommandBusInterface.
 *
 * @package Drupal\command_bus
 */
interface CommandBusInterface {

  /**
   * Resolves the command to a handler and handles the command handler.
   *
   * @param \Drupal\command_bus\Command\CommandInterface $command
   *   The command.
   */
  public function execute(CommandInterface $command);

}
