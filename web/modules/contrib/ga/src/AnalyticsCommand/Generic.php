<?php

namespace Drupal\ga\AnalyticsCommand;

/**
 * Class FieldsObject.
 */
class Generic implements DrupalSettingCommandsInterface {

  use DrupalSettingCommandsTrait;

  const DEFAULT_PRIORITY = 0;

  /**
   * The command name.
   *
   * @var string
   */
  protected $command;

  /**
   * A map of values for the command's fieldsObject parameter.
   *
   * @var array
   */
  protected $fieldsObject;

  /**
   * The name of the tracker for this command.
   *
   * @var string
   */
  protected $trackerName;

  /**
   * AnalyticsCommand constructor.
   *
   * @param string $command
   *   The command name.
   * @param array $fields_object
   *   A map of values for the command's fieldsObject parameter.
   * @param string $tracker_name
   *   The tracker name (optional).
   * @param int $priority
   *   The command priority.
   */
  public function __construct($command, array $fields_object = [], $tracker_name = NULL, $priority = self::DEFAULT_PRIORITY) {
    $this->command = $command;
    $this->fieldsObject = $fields_object;
    $this->trackerName = $tracker_name;
    $this->priority = $priority;
  }

  /**
   * Get the command name.
   *
   * @return string
   *   The command name.
   */
  public function getCommand() {
    return $this->command;
  }

  /**
   * Get the map of values for the command's fieldsObject parameter.
   *
   * @return array
   *   An array of command values.
   */
  public function getFieldsObject() {
    return $this->fieldsObject;
  }

  /**
   * The tracker this command will be applied to, if specified.
   *
   * @return null|string
   *   The tracker name, or NULL if the default tracker.
   */
  public function getTrackerName() {
    return $this->trackerName;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingCommands() {
    $command = [
      ($this->trackerName ? $this->trackerName . '.' : '') . $this->command,
    ];

    if (!empty($this->fieldsObject)) {
      $command[] = $this->fieldsObject;
    }

    return [$command];
  }

}
