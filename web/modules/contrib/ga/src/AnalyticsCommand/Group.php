<?php

namespace Drupal\ga\AnalyticsCommand;

/**
 * Class Drupal\ga\AnalyticsCommand\Group.
 */
class Group implements DrupalSettingCommandsInterface, GroupInterface, \IteratorAggregate {

  use DrupalSettingCommandsTrait;

  const DEFAULT_PRIORITY = 0;

  /**
   * A key to identify this group.
   *
   * @var string
   */
  protected $groupKey;

  /**
   * The commands within this group.
   *
   * @var array[DrupalSettingsCommandsInterface]
   */
  protected $commands;

  /**
   * AnalyticsCommandGroup constructor.
   *
   * @param string $key
   *   The group name.
   * @param int $priority
   *   The group priority.
   */
  public function __construct($key, $priority = self::DEFAULT_PRIORITY) {
    $this->groupKey = $key;
    $this->priority = $priority;
    $this->commands = [];
  }

  /**
   * Get the key identifying this group.
   *
   * @return string
   *   The group key.
   */
  public function getGroupKey() {
    return $this->groupKey;
  }

  /**
   * Add a command to the group.
   *
   * @param \Drupal\ga\AnalyticsCommand\DrupalSettingCommandsInterface $command
   *   A command.
   */
  public function addCommand(DrupalSettingCommandsInterface $command) {
    $this->commands[] = $command;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingCommands() {

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

  /**
   * Retrieve an external iterator.
   *
   * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
   *
   * @return \ArrayIterator
   *   An iterator for the group's commands.
   */
  public function getIterator() {
    return new \ArrayIterator($this->commands);
  }

}
