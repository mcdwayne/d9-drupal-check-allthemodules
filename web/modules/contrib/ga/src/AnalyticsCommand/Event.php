<?php

namespace Drupal\ga\AnalyticsCommand;

/**
 * Class Event.
 */
class Event extends Send {

  /**
   * The event category.
   *
   * @var string
   */
  protected $eventCategory;

  /**
   * The event action.
   *
   * @var string
   */
  protected $eventAction;

  /**
   * The event label.
   *
   * @var null|string
   */
  protected $eventLabel;

  /**
   * The event value.
   *
   * @var int
   */
  protected $eventValue;

  /**
   * Create constructor.
   *
   * @param string $event_category
   *   The event category.
   * @param string $event_action
   *   The event action.
   * @param string $event_label
   *   The event label (optional).
   * @param int $event_value
   *   The event value (optional).
   * @param array $fields_object
   *   A map of values for the command's fieldsObject parameter.
   * @param string $tracker_name
   *   The tracker name (optional).
   * @param int $priority
   *   The command priority.
   */
  public function __construct($event_category, $event_action, $event_label = NULL, $event_value = NULL, array $fields_object = [], $tracker_name = NULL, $priority = self::DEFAULT_PRIORITY) {

    if (!is_null($event_value) && (!is_int($event_value) || $event_value < 0)) {
      throw new \InvalidArgumentException("Event value must be a positive integer");
    }

    parent::__construct('event', $fields_object, $tracker_name, $priority);

    $this->eventCategory = $event_category;
    $this->eventAction = $event_action;
    $this->eventLabel = $event_label;
    $this->eventValue = $event_value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingCommands() {
    $command = [
      ($this->trackerName ? $this->trackerName . '.' : '') . $this->command,
      $this->hitType,
      $this->eventCategory,
      $this->eventAction,
    ];

    $fieldsObject = $this->fieldsObject;

    if (!empty($this->eventLabel)) {
      $command[] = $this->eventLabel;

      if (!is_null($this->eventValue)) {
        $command[] = $this->eventValue;
      }
    }
    elseif (!is_null($this->eventValue)) {
      // If label is not specified but value is, the value must be set in
      // fieldsObject instead.
      $fieldsObject['eventValue'] = $this->eventValue;
    }

    if (!empty($fieldsObject)) {
      $command[] = $fieldsObject;
    }

    return [$command];
  }

}
