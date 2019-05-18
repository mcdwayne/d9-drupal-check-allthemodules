<?php

namespace Drupal\ga\Event;

use Drupal\ga\AnalyticsCommand\DrupalSettingCommandsInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CollectEvent.
 */
class CollectEvent extends Event {

  /**
   * The registered analytics commands.
   *
   * @var \Drupal\ga\AnalyticsCommand\DrupalSettingCommandsInterface[]
   */
  protected $commands;

  /**
   * CollectEvent constructor.
   */
  public function __construct() {
    $this->commands = [];
  }

  /**
   * Add a command.
   *
   * @param \Drupal\ga\AnalyticsCommand\DrupalSettingCommandsInterface $command
   *   An analytics command.
   */
  public function addCommand(DrupalSettingCommandsInterface $command) {
    $this->commands[] = $command;
  }

  /**
   * Format the commands for use in drupalSettings.
   *
   * @return array
   *   An array of commands for use in drupalSettings.
   */
  public function getDrupalSettingCommands() {
    usort($this->commands, function (DrupalSettingCommandsInterface $a, DrupalSettingCommandsInterface $b) {
      return $b->getPriority() - $a->getPriority();
    });

    return array_reduce(
      $this->commands,
      function ($carry, DrupalSettingCommandsInterface $item) {
        return array_merge($carry, $item->getSettingCommands());
      },
      []
    );
  }

}
