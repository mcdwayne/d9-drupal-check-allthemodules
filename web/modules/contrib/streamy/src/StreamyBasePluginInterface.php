<?php

namespace Drupal\streamy;

/**
 * Interface StreamyBasePluginInterface
 *
 * @package Drupal\streamy
 */
interface StreamyBasePluginInterface {

  /**
   * Gets the settings of the current plugin in a formatted array format.
   *
   * @param string $scheme
   * @param array  $config
   * @return array
   */
  public function getPluginSettings(string $scheme, string $level, array $config = []);

  /**
   * Ensures that the current plugin works properly.
   * Returns a MountManager instance or the catched error message.
   *
   * @param string $scheme
   * @param array  $config
   * @return \League\Flysystem\MountManager|bool
   */
  public function ensure(string $scheme, string $level, array $config = []);

}
