<?php
/**
 * @file
 * Provides Drupal\flashpoint\FlashpointAccessMethodInterface
 */
namespace Drupal\flashpoint;
/**
 * An interface for all MyPlugin type plugins.
 */
interface FlashpointAccessMethodInterface {
  /**
   * Provide a description of the plugin.
   * @return string
   *   A string description of the plugin.
   */
  public function description();
}