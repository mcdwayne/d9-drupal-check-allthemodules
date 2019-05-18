<?php

namespace Drupal\ga\AnalyticsCommand;

/**
 * Class Timing.
 */
class Timing extends Send {

  /**
   * A string for categorizing all user timing variables into logical groups.
   *
   * @var string
   */
  protected $timingCategory;

  /**
   * A string to identify the variable being recorded.
   *
   * @var string
   */
  protected $timingVar;

  /**
   * The number of milliseconds in elapsed time.
   *
   * @var int
   */
  protected $timingValue;

  /**
   * A string that can be used to add flexibility in visualizing user timings.
   *
   * @var null|string
   */
  protected $timingLabel;

  /**
   * Create constructor.
   *
   * @param string $category
   *   A string for categorizing all user timing variables into logical groups.
   * @param string $var
   *   A string to identify the variable being recorded.
   * @param int $value
   *   The number of milliseconds in elapsed time.
   * @param string|null $label
   *   A string that can be used to add flexibility in visualizing user timings
   *   in the reports.
   * @param array $fields_object
   *   A map of values for the command's fieldsObject parameter.
   * @param string $tracker_name
   *   The tracker name (optional).
   * @param int $priority
   *   The command priority.
   */
  public function __construct($category, $var, $value, $label = NULL, array $fields_object = [], $tracker_name = NULL, $priority = self::DEFAULT_PRIORITY) {
    $this->timingCategory = $category;
    $this->timingVar = $var;
    if (!is_int($value)) {
      throw new \InvalidArgumentException("Timing value must be an integer");
    }
    $this->timingValue = $value;
    $this->timingLabel = $label;
    parent::__construct('timing', $fields_object, $tracker_name, $priority);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingCommands() {
    $command = [
      ($this->trackerName ? $this->trackerName . '.' : '') . $this->command,
      $this->hitType,
      $this->timingCategory,
      $this->timingVar,
      $this->timingValue,
    ];

    if (!is_null($this->timingLabel)) {
      $command[] = $this->timingLabel;
    }

    if (!empty($this->fieldsObject)) {
      $command[] = $this->fieldsObject;
    }

    return [$command];
  }

}
