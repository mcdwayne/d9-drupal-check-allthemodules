<?php

/**
 * @file
 * Provides Drupal\task\TaskBundleInterface;
 */

namespace Drupal\task;

/**
 * An interface for all TaskBundle type plugins.
 */
interface TaskBundleInterface {
  /**
   * Provide a description of the plugin.
   * @return string
   *   A string description of the plugin.
   */
  public function description();
}