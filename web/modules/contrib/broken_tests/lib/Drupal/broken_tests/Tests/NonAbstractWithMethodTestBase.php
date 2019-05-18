<?php

/**
 * @file
 * Definition of Drupal\broken_tests\Tests\NonAbstractWithMethodTestBase.
 */

namespace Drupal\broken_tests\Tests;
use Drupal\simpletest\UnitTestBase;

/**
 * Defines a non-abstract base class with a misplaced test method.
 */
class NonAbstractWithMethodTestBase extends UnitTestBase {

  /**
   * Modules to enable.
   */
  public static $modules = array('broken_tests');

  public function setUp() {
    parent::setUp();
    $this->verbose('Setup executed for NonAbstractWithMethodTestBase (a non-abstract base class with a misplaced test method).');
  }

  /**
   * Provides a helper method to child classes.
   */
  function helper() {
    $this->verbose('Helper from NonAbstractWithMethodTestBase executed.');
  }

  /**
   * Executes a test.
   */
  function testMisplacedMethod() {
    $this->helper();
    $this->verbose('Misplaced test method in NonAbstractWithMethodTestBase executed.');
  }

}
