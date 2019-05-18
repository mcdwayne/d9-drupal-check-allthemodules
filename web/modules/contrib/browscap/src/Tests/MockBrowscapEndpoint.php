<?php

namespace Drupal\browscap\Tests;

use Drupal\browscap\BrowscapEndpoint;

/**
 * Mock Browscap endpoint.
 *
 * Defines methods for simulating communication with Browscap project website
 * using local information.
 */
class MockBrowscapEndpoint extends BrowscapEndpoint {

  /**
   * Gets version of latest Browscap data.
   *
   * @return int|string
   *   The latest Browscap data version.
   */
  public function getVersion() {
    // Check the local browscap data version number.
    $config = \Drupal::config('browscap.settings');

    $local_version = $config->get('version');
    $fake_version = $local_version . '1';

    return $fake_version;
  }

  /**
   * Gets latest Browscap data.
   *
   * @param bool $cron
   *   Whether this method is being invoked by cron.
   *
   * @return int|string
   *   The Browscap data.
   */
  public function getBrowscapData($cron = TRUE) {
    $ini_path = drupal_get_path('module', 'browscap') . '/src/Tests/test_browscap_data.ini';
    $browscap_data = file_get_contents($ini_path);

    return $browscap_data;
  }

}
