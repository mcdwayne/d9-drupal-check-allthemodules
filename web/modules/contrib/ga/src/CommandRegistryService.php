<?php

namespace Drupal\ga;

use Drupal\ga\AnalyticsCommand\DrupalSettingCommandsInterface;

/**
 * Class CommandRegistryService.
 */
class CommandRegistryService {

  /**
   * The registered analytics commands.
   *
   * @var \Drupal\ga\AnalyticsCommand\DrupalSettingCommandsInterface[]
   */
  protected $commands;

  /**
   * CommandRegistryService constructor.
   */
  public function __construct() {
    $this->commands = [];
  }

  /**
   * Add a command to the registry.
   *
   * @param \Drupal\ga\AnalyticsCommand\DrupalSettingCommandsInterface $command
   *   An analytics command.
   *
   * @deprecated Use addCommand() instead.
   */
  public function addItem(DrupalSettingCommandsInterface $command) {
    $this->addCommand($command);
  }

  /**
   * Add a command to the registry.
   *
   * @param \Drupal\ga\AnalyticsCommand\DrupalSettingCommandsInterface $command
   *   An analytics command.
   */
  public function addCommand(DrupalSettingCommandsInterface $command) {
    $this->commands[] = $command;
  }

  /**
   * Get all commands registered.
   *
   * @return \Drupal\ga\AnalyticsCommand\DrupalSettingCommandsInterface[]
   *   The array of registered commands.
   */
  public function getCommands() {
    return $this->commands;
  }

}
