<?php
/**
 * @file
 * Provides Drupal\flashpoint\FlashpointSettingsInterface
 */
namespace Drupal\flashpoint;
/**
 * An interface for all MyPlugin type plugins.
 */
interface FlashpointSettingsInterface {
  /**
   * Provide a description of the plugin.
   * @return string
   *   A string description of the plugin.
   */
  public function description();

  /**
   * Provide form options for the settings form.
   * @return array
   *   Array of Form API form elements. This will be keyed to the plugin ID as $form[plugin_id] = {return_value};
   */
  public static function getFormOptions();
}