<?php

namespace Drupal\commander\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Command handler plugins.
 */
interface CommandHandlerInterface extends PluginInspectionInterface {

  /**
   * Executes the command.
   *
   * @param object $command
   *   Command object.
   */
  public function execute($command);

}
