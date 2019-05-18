<?php

namespace Drupal\hotjar;

/**
 * Interface HotjarSettingsInterface.
 *
 * @package Drupal\hotjar
 */
interface HotjarSettingsInterface {

  /**
   * Get all settings.
   *
   * @return array
   *   Get settings.
   */
  public function getSettings();

  /**
   * Get setting.
   *
   * @param string $key
   *   Settings key.
   * @param mixed $default
   *   Default value.
   *
   * @return mixed
   *   Setting value.
   */
  public function getSetting($key, $default = NULL);

}
