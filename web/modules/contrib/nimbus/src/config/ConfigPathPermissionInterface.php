<?php

namespace Drupal\nimbus\config;

/**
 * Interface ConfigPathPermissionInterface.
 *
 * @package Drupal\nimbus\config
 */
interface ConfigPathPermissionInterface {

  /**
   * Validate the read permission.
   *
   * @param string $name
   *   The name of the config file.
   *
   * @return bool
   *   Has permission true or has no permission false.
   */
  public function hasReadPermission($name);

  /**
   * Validate the write permission.
   *
   * @param string $name
   *   The name of the config file.
   * @param array $data
   *   The value of the config file.
   *
   * @return bool
   *   Has permission true or has no permission false.
   */
  public function hasWritePermission($name, array &$data);

  /**
   * Validate the delete permission.
   *
   * @param string $name
   *   The name of the config file.
   *
   * @return bool
   *   Has permission true or has no permission false.
   */
  public function hasDeletePermission($name);

}
