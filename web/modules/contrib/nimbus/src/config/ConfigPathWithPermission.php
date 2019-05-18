<?php

namespace Drupal\nimbus\config;

/**
 * Class ConfigPath.
 *
 * @package Drupal\nimbus\config
 */
class ConfigPathWithPermission extends ConfigPath implements ConfigPathPermissionInterface {

  /**
   * ConfigPathWithPermission constructor.
   *
   * @param string $config_path
   *   The config path.
   * @param bool $readPermission
   *   The read permission.
   * @param bool $writePermission
   *   The write permission.
   * @param bool $deletePermission
   *   The delete permission.
   */
  public function __construct($config_path, $readPermission = FALSE, $writePermission = FALSE, $deletePermission = FALSE) {
    parent::__construct($config_path);
    $this->addAdditionalInformation('read', $readPermission);
    $this->addAdditionalInformation('write', $writePermission);
    $this->addAdditionalInformation('delete', $deletePermission);
  }

  /**
   * {@inheritdoc}
   */
  public function hasReadPermission($name) {
    $response = $this->getAdditionalInformationByKey('read');
    if ($response instanceof \Closure) {
      $response = $response($name);
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function hasWritePermission($name, array &$data) {
    $response = $this->getAdditionalInformationByKey('write');
    if ($response instanceof \Closure) {
      $response = $response($name);
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function hasDeletePermission($name) {
    $response = $this->getAdditionalInformationByKey('delete');
    if ($response instanceof \Closure) {
      $response = $response($name);
    }
    return $response;
  }

}
