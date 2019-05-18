<?php

namespace Drupal\developer_suite;

use Drupal\developer_suite\Command\CommandInterface;

/**
 * Interface DefaultCommandBusInterface.
 *
 * @package Drupal\developer_suite
 */
interface CommandBusInterface {

  /**
   * Resolves the command to a handler and handles the command handler.
   *
   * @param \Drupal\developer_suite\Command\CommandInterface $command
   *   The command.
   */
  public function execute(CommandInterface $command);

}
