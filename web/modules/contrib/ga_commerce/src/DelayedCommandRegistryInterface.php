<?php

namespace Drupal\ga_commerce;

use Drupal\ga\AnalyticsCommand\DrupalSettingCommandsInterface;

/**
 * Defines the delayed command registry interface.
 */
interface DelayedCommandRegistryInterface {

  /**
   * Add a command to the registry.
   *
   * @param \Drupal\ga\AnalyticsCommand\DrupalSettingCommandsInterface $command
   *   An analytics command.
   */
  public function addCommand(DrupalSettingCommandsInterface $command);

  /**
   * Get all commands registered.
   *
   * @return \Drupal\ga\AnalyticsCommand\DrupalSettingCommandsInterface[]
   *   The array of registered commands.
   */
  public function getCommands();

}
