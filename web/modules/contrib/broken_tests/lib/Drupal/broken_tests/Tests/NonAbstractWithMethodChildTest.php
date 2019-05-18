<?php

/**
 * @file
 * Definition of Drupal\broken_tests\Tests\NonAbstractWithMethodChildTest.
 */

namespace Drupal\broken_tests\Tests;
use Drupal\simpletest\UnitTestBase;

/**
 * Defines a child class extending NonAbstractWithMethodTestBase.
 */
class NonAbstractWithMethodChildTest extends NonAbstractWithMethodTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Child of NonAbstractWithMethodTestBase',
      'description' => 'A child class extending a broken base class that is not abstract and includes a misplaced test method.',
      'group' => 'Broken tests',
    );
  }

  /**
   * Modules to enable.
   */
  public static $modules = array('broken_tests');

  public function setUp() {
    parent::setUp();
    $this->verbose('Setup executed for NonAbstractWithMethodChildTest (a normal test class).');
  }

  /**
   * Executes a test.
   */
  function testActualTestMethod() {
    $this->helper();
    $this->verbose('Test method in NonAbstractWithMethodChildTest executed.');
  }

}
