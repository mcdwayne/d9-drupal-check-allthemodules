<?php

/**
 * @file
 * Definition of Drupal\broken_tests\Tests\NonAbstractChildTest.
 */

namespace Drupal\broken_tests\Tests;
use Drupal\simpletest\UnitTestBase;

/**
 * Defines a child class extending NonAbstractTestBase.
 */
class NonAbstractChildTest extends NonAbstractTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Child of NonAbstractTestBase',
      'description' => 'A child class extending a base class that is not abstract.',
      'group' => 'Broken tests',
    );
  }

  /**
   * Modules to enable.
   */
  public static $modules = array('broken_tests');

  public function setUp() {
    parent::setUp();
    $this->verbose('Setup executed for NonAbstractChildTest (a normal test class).');
  }

  /**
   * Executes a test.
   */
  function testActualTestMethod() {
    $this->helper();
    $this->verbose('Test method in NonAbstractChildTest executed.');
  }

}
