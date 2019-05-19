<?php

/**
 * @file
 * SmartlingApiFactory.php.
 */

namespace Drupal\tmgmt_smartling\Smartling;

use Smartling\BaseApiAbstract;

/**
 * Class ConnectorInfo
 * @package Drupal\tmgmt_smartling\Smartling
 */
class ConnectorInfo {

  /**
   * Returns module name.
   *
   * @return string
   */
  public static function getLibName() {
    return 'drupal-tmgmt-connector';
  }

  /**
   * Returns module version.
   *
   * @param string $name
   * @param string $default
   * @return string
   */
  public static function getLibVersion($name = 'tmgmt_smartling', $default = 'unknown') {
    $info = system_get_info('module', $name);
    $client_version = $default;

    if (!empty($info['version'])) {
      $client_version = $info['version'];
    }

    return $client_version;
  }

  public static function getDependenciesVersionsAsString() {
    $result = [];
    $dependencies = [
      'tmgmt_extension_suit',
      'tmgmt',
    ];

    foreach ($dependencies as $dependency) {
      $result[] = "${dependency}/" . self::getLibVersion($dependency);
    }

    return implode(' ', $result);
  }

  /**
   * Set up current client id and version.
   */
  public static function setUpCurrentClientInfo() {
    BaseApiAbstract::setCurrentClientId(self::getLibName());
    BaseApiAbstract::setCurrentClientVersion(self::getLibVersion());
    BaseApiAbstract::setCurrentClientUserAgentExtension(self::getDependenciesVersionsAsString());
  }

}
