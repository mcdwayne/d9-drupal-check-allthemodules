<?php

/**
 * @file
 * Provides Drupal\task\TaskActionInterface;
 */

namespace Drupal\task;

/**
 * An interface for all TaskAction type plugins.
 */
interface TaskActionInterface {
  /**
   * Provide a description of the plugin.
   * @return string
   *   A string description of the plugin.
   */
  public function description();
}