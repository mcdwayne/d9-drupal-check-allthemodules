<?php

namespace Drupal\streamy;

/**
 * Interface StreamyCDNInterface
 *
 * @package Drupal\streamy
 */
interface StreamyCDNInterface {

  /**
   * Gets the settings of the current plugin in a formatted array format.
   *
   * @param string $scheme
   * @param array  $config
   * @return array
   */
  public function getPluginSettings(string $scheme, array $config = []);

  /**
   * Ensures that the current plugin works properly.
   * Returns a MountManager instance or the catched error message.
   *
   * @param string $scheme
   * @param array  $config
   * @return \League\Flysystem\MountManager|bool
   */
  public function ensure(string $scheme, array $config = []);

  /**
   * Returns a valid external URL or null.
   *
   * @param        $uri
   * @param string $scheme
   * @return string|null
   */
  public function getExternalUrl($uri, string $scheme);

}
