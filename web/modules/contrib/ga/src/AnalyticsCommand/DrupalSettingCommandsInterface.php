<?php

namespace Drupal\ga\AnalyticsCommand;

/**
 * Interface AnalyticsCommandSetInterface.
 */
interface DrupalSettingCommandsInterface {

  /**
   * An integer value for sorting by priority.
   *
   * @return int
   *   The priority value.
   */
  public function getPriority();

  /**
   * An array of commands to be sent to Google Analytics.
   *
   * @return array
   *   An array of command values.
   */
  public function getSettingCommands();

}
