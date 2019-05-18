<?php
/**
 * @file
 * Provides Drupal\flashpoint_course_content\FlashpointCourseContentRendererInterface;
 */
namespace Drupal\flashpoint_course_content;
/**
 * An interface for all FlashpointCourseContentRenderer type plugins.
 */
interface FlashpointCourseContentRendererInterface {
  /**
   * Provide a description of the plugin.
   * @return string
   *   A string description of the plugin.
   */
  public function description();
}