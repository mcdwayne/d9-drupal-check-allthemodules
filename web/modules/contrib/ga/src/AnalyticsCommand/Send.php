<?php

namespace Drupal\ga\AnalyticsCommand;

/**
 * Class Send.
 */
class Send extends Generic {

  const DEFAULT_PRIORITY = -10;

  private static $hitTypes = [
    "pageview",
    "event",
    "social",
    "timing",
    "exception",
  ];

  /**
   * The event hitType parameter.
   *
   * @var string
   */
  protected $hitType;

  /**
   * Send constructor.
   *
   * @param string $hit_type
   *   The event hitType.
   * @param array $fields_object
   *   A map of values for the command's fieldsObject parameter.
   * @param string $tracker_name
   *   The tracker name (optional).
   * @param int $priority
   *   The command priority.
   */
  public function __construct($hit_type, array $fields_object = [], $tracker_name = NULL, $priority = self::DEFAULT_PRIORITY) {

    if (!in_array($hit_type, self::$hitTypes)) {
      throw new \InvalidArgumentException("Invalid hit type specified.");
    }

    parent::__construct('send', $fields_object, $tracker_name, $priority);

    $this->hitType = $hit_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingCommands() {
    $command = [
      ($this->trackerName ? $this->trackerName . '.' : '') . $this->command,
      $this->hitType,
    ];

    if (!empty($this->fieldsObject)) {
      $command[] = $this->fieldsObject;
    }

    return [$command];
  }

}
