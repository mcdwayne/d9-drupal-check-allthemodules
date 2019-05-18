<?php

namespace Drupal\flashpoint_course;

/**
 * An interface for all FlashpointCourseRenderer type plugins.
 */
interface FlashpointCourseRendererInterface {
  /**
   * Provide a description of the plugin.
   * @return string
   *   A string description of the plugin.
   */
  public function description();
}