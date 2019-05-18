<?php

namespace Drupal\commander\Contracts;

/**
 * Interface CommandBus.
 */
interface CommandBusInterface {

  /**
   * Executes a command.
   *
   * @param \Drupal\commander\Contracts\CommandInterface $command
   *   Command object.
   *
   * @return mixed
   *   Command execution result.
   */
  public function execute(CommandInterface $command);

}
