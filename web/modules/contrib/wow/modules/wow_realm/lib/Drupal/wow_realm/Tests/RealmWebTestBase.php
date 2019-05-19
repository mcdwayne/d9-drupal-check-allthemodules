<?php

/**
 * @file
 * Definition of RealmTestBase.
 */

namespace Drupal\wow_realm\Tests;

use Drupal\wow\Tests\WebTestBase;

class RealmWebTestBase extends WebTestBase {

  protected function setUp() {
    // Since this is a base class for many test cases, support the same
    // flexibility that DrupalWebTestCase::setUp() has for the modules to be
    // passed in as either an array or a variable number of string arguments.
    $modules = func_get_args();
    if (isset($modules[0]) && is_array($modules[0])) {
      $modules = $modules[0];
    }
    $modules[] = 'wow_realm';
    parent::setUp($modules);
  }

}
