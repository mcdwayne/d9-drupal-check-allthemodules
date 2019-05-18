<?php

/**
 * @file
 * Definition of Drupal\broken_tests\Tests\NonAbstractTestBase.
 */

namespace Drupal\broken_tests\Tests;
use Drupal\simpletest\UnitTestBase;

/**
 * Defines a non-abstract base class without a test method.
 */
class NonAbstractTestBase extends UnitTestBase {

  /**
   * Modules to enable.
   */
  public static $modules = array('broken_tests');

  public function setUp() {
    parent::setUp();
    $this->verbose('Setup executed for NonAbstractTestBase (a non-abstract base class with no test methods).');
  }

  /**
   * Provides a helper method to child classes.
   */
  function helper() {
    $this->verbose('Helper from NonAbstractTestBase executed.');
  }

}
