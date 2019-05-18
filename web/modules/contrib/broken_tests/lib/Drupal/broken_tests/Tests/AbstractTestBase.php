<?php

/**
 * @file
 * Definition of Drupal\broken_tests\Tests\AbstractTestBase.
 */

namespace Drupal\broken_tests\Tests;
use Drupal\simpletest\UnitTestBase;

/**
 * Defines an abstract base class without a test method.
 */
abstract class AbstractTestBase extends UnitTestBase {

  /**
   * Modules to enable.
   */
  public static $modules = array('broken_tests');

  public function setUp() {
    parent::setUp();
    $this->verbose('Setup executed for AbstractTestBase (an abstract base class with no test methods).');
  }

  /**
   * Provides a helper method to child classes.
   */
  function helper() {
    $this->verbose('Helper from AbstractTestBase executed.');
  }

}
