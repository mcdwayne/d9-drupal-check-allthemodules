<?php

namespace Drupal\apptiles\ApplicationTiles;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Application tiles interface.
 */
interface ManagerInterface extends ContainerInjectionInterface {

  /**
   * The list of operating systems the tiles can be configured for.
   */
  const TYPES = ['ios', 'android', 'windows'];

  /**
   * Get list of settings.
   *
   * @return array
   *   List of settings.
   */
  public function getSettings();

  /**
   * Get value of particular setting.
   *
   * @param string $setting
   *   Name of setting.
   * @param mixed $default_value
   *   Value to return if specified setting was not set.
   *
   * @return mixed
   *   Value of setting.
   */
  public function getSetting($setting, $default_value = NULL);

  /**
   * Get path to directory with tiles.
   *
   * @return string
   *   Path to directory.
   */
  public function getPath();

  /**
   * Get absolute links for existing tiles.
   *
   * @return array[]
   *   Arrays, grouped by OS name and dimension.
   */
  public function getUrls();

  /**
   * Checks whether configuration available for output.
   *
   * @return bool
   *   A state of check.
   */
  public function isAvailable();

}
