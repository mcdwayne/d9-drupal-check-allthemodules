<?php

namespace Drupal\ga\AnalyticsCommand;

/**
 * Class RequirePlugin.
 *
 * Since 'require' is a php reserved word, the class name needs to be longer.
 */
class RequirePlugin extends Generic {

  const DEFAULT_PRIORITY = 250;

  /**
   * The plugin name.
   *
   * @var string
   */
  protected $pluginName;

  /**
   * Create constructor.
   *
   * @param string $plugin_name
   *   The plugin name.
   * @param array $fields_object
   *   A map of values for the command's fieldsObject parameter.
   * @param string $tracker_name
   *   The tracker name (optional).
   * @param int $priority
   *   The command priority.
   */
  public function __construct($plugin_name, array $fields_object = [], $tracker_name = NULL, $priority = self::DEFAULT_PRIORITY) {
    parent::__construct('require', $fields_object, $tracker_name, $priority);

    $this->pluginName = $plugin_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingCommands() {
    $command = [
      ($this->trackerName ? $this->trackerName . '.' : '') . $this->command,
      $this->pluginName,
    ];

    if (!empty($this->fieldsObject)) {
      $command[] = $this->fieldsObject;
    }

    return [$command];
  }

}
