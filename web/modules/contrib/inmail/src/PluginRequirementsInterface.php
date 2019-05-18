<?php

namespace Drupal\inmail;

/**
 * Provides methods to check requirements of Inmail's plugins (deliverers, analyzers, handlers).
 */
interface PluginRequirementsInterface {

  /**
   * Checks requirements of the current plugin.
   *
   * @return array
   *   Returns the structured verbose output if requirements are not met.
   *   In case of no plugin requirements, an empty array is returned.
   */
  public static function checkPluginRequirements();

  /**
   * Checks requirements of the current configuration instance.
   *
   * @return array
   *   Returns the structured verbose output if requirements are not met.
   *   In case of no instance requirements, an empty array is returned.
   */
  public function checkInstanceRequirements();

  /**
   * Flag determining whether a plugin is available to be used in processing.
   *
   * @return bool
   *   TRUE if the plugin is available. Otherwise, FALSE.
   */
  public function isAvailable();

}
