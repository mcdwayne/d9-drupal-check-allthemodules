<?php

namespace Drupal\ga\AnalyticsCommand;

/**
 * Trait AnalyticsCommandSetTrait.
 *
 * Trait for implementing DrupalSettingCommandsInterface.
 */
trait DrupalSettingCommandsTrait {

  /**
   * Priority integer.
   *
   * @var int
   */
  protected $priority;

  /**
   * An integer value for sorting by priority.
   *
   * @return int
   *   The priority value.
   */
  public function getPriority() {
    return $this->priority;
  }

  /**
   * An array of commands to be sent to Google Analytics.
   *
   * @return array
   *   An array of command values.
   */
  public function getSettingCommands() {
    return [];
  }

}
