<?php

namespace Drupal\prometheus_exporter\Plugin\MetricsCollector;

/**
 * A facade for PHP global functions to decouple and facilitate testing.
 */
class PhpVersion {

  /**
   * Gets the version string.
   *
   * @return string
   *   The version string.
   */
  public function getString() {
    return phpversion();
  }

  /**
   * Gets the version ID.
   *
   * @return int
   *   The version ID.
   */
  public function getId() {
    return PHP_VERSION_ID;
  }

  /**
   * The PHP major version.
   *
   * @return int
   *   The major version.
   */
  public function getMajor() {
    return PHP_MAJOR_VERSION;
  }

  /**
   * The PHP minor version.
   *
   * @return int
   *   The minor version.
   */
  public function getMinor() {
    return PHP_MINOR_VERSION;
  }

  /**
   * The PHP patch version.
   *
   * @return int
   *   The patch version.
   */
  public function getPatch() {
    return PHP_RELEASE_VERSION;
  }

}
