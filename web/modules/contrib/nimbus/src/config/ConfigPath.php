<?php

namespace Drupal\nimbus\config;

/**
 * Class ConfigPath.
 *
 * @package Drupal\nimbus\config
 */
class ConfigPath implements ConfigPathPermissionInterface {

  /**
   * The wrapped path.
   *
   * @var string
   */
  private $configPath;

  /**
   * Additional Information about the path.
   *
   * @var array
   */
  private $additionalInformation = [];

  /**
   * ConfigPath constructor.
   *
   * @param string $config_path
   *   The path that should be wrapped.
   */
  public function __construct($config_path) {
    $this->configPath = $config_path;
  }

  /**
   * Add method for additional information's.
   *
   * @param string $key
   *   The key for additional information.
   * @param mixed $info
   *   The additional information.
   */
  public function addAdditionalInformation($key, $info) {
    $this->additionalInformation[$key] = $info;
  }

  /**
   * Getter for additionalInformation.
   *
   * @return array
   *   Return all additional information's of this path.
   */
  public function getAdditionalInformation() {
    return $this->additionalInformation;
  }

  /**
   * Getter for the additionalInformation store.
   *
   * @param string $key
   *   The key from that you want the value.
   *
   * @return mixed|null
   *   Return the value behind the key or if the not exist null.
   */
  public function getAdditionalInformationByKey($key) {
    return isset($this->additionalInformation[$key]) ? $this->additionalInformation[$key] : NULL;
  }

  /**
   * Magic to string method.
   *
   * @return string
   *   The wrapped path.
   */
  public function __toString() {
    return $this->configPath;
  }

  /**
   * {@inheritdoc}
   */
  public function hasReadPermission($name) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasWritePermission($name, array &$data) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasDeletePermission($name) {
    return TRUE;
  }

}
