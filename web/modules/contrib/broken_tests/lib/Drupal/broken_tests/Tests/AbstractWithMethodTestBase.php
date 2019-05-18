<?php

/**
 * @file
 * Definition of Drupal\broken_tests\Tests\AbstractWithMethodTestBase.
 */

namespace Drupal\broken_tests\Tests;
use Drupal\simpletest\UnitTestBase;

/**
 * Defines an abstract base class with a misplaced test method.
 */
abstract class AbstractWithMethodTestBase extends UnitTestBase {

  /**
   * Modules to enable.
   */
  public static $modules = array('broken_tests');

  public function setUp() {
    parent::setUp();
    $this->verbose('Setup executed for AbstractWithMethodTestBase (an abstract base class with a misplaced test method).');
  }

  /**
   * Provides a helper method to child classes.
   */
  function helper() {
    $this->verbose('Helper from AbstractWithMethodTestBase executed.');
  }

  /**
   * Executes a test.
   */
  function testMisplacedMethod() {
    $this->helper();
    $this->verbose('Misplaced test method in AbstractWithMethodTestBase executed.');
  }

}
