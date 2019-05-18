<?php

namespace Drupal\ga\AnalyticsCommand;

/**
 * Class Set.
 */
class Set extends Generic {

  const DEFAULT_PRIORITY = 100;

  /**
   * The setting key.
   *
   * @var string
   */
  protected $settingKey;

  /**
   * The setting value.
   *
   * @var mixed
   */
  protected $settingValue;

  /**
   * Create constructor.
   *
   * @param string $setting_key
   *   The setting key.
   * @param mixed $value
   *   The setting value.
   * @param array $fields_object
   *   A set of additional options for the command.
   * @param string $tracker_name
   *   The tracker name.
   * @param int $priority
   *   The command priority.
   */
  public function __construct($setting_key, $value, array $fields_object = [], $tracker_name = NULL, $priority = self::DEFAULT_PRIORITY) {
    parent::__construct('set', $fields_object, $tracker_name, $priority);
    $this->settingKey = $setting_key;
    $this->settingValue = $value;
  }

  /**
   * Get the setting key.
   *
   * @return string
   *   The setting key.
   */
  public function getSettingKey() {
    return $this->settingKey;
  }

  /**
   * Get the setting value.
   *
   * @return mixed
   *   The setting value.
   */
  public function getSettingValue() {
    return $this->settingValue;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingCommands() {
    $command = [
      ($this->trackerName ? $this->trackerName . '.' : '') . $this->command,
      $this->settingKey,
      $this->settingValue,
    ];

    if (!empty($this->fieldsObject)) {
      $command[] = $this->fieldsObject;
    }

    return [$command];
  }

}
