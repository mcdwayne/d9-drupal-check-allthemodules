<?php

/**
 * @file
 * Helper class for WoW test base classes.
 */

namespace Drupal\wow\Tests;

/**
 * Defines WebTestBase class test.
 */
class WebTestBase extends \DrupalWebTestCase {

  protected function setUp() {
    // Since this is a base class for many test cases, support the same
    // flexibility that DrupalWebTestCase::setUp() has for the modules to be
    // passed in as either an array or a variable number of string arguments.
    $modules = func_get_args();
    if (isset($modules[0]) && is_array($modules[0])) {
      $modules = $modules[0];
    }
    $modules[] = 'wow_test';
    parent::setUp($modules);
  }

  /**
   * Generates a random region.
   */
  public static function randomRegion() {
    return array_rand(array_flip(array_keys(wow_service_info())));
  }

}
