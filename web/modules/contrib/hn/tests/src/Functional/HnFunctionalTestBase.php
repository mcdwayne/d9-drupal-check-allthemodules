<?php

namespace Drupal\Tests\hn\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Provides helper methods for the HN module's functional tests.
 */
abstract class HnFunctionalTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'hn_test',
  ];

  /**
   * Gets an Hn Response from a path.
   */
  protected function getHnResponse($path, $options = []) {
    $options = $options + ['path' => $path];

    return $this->drupalGet($this->getAbsoluteUrl('hn?' . http_build_query($options)));
  }

  /**
   * Gets an Hn Response from a path, and converts it to an associative array.
   */
  protected function getHnJsonResponse($path, $options = []) {
    return json_decode($this->getHnResponse($path, $options), TRUE);
  }

}
