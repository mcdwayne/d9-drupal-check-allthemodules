<?php

/**
 * @file
 * Definition of Drupal\broken_tests\Tests\AbstractWithMethodChildTest.
 */

namespace Drupal\broken_tests\Tests;
use Drupal\simpletest\UnitTestBase;

/**
 * Defines a child class extending AbstractWithMethodTestBase.
 */
class AbstractWithMethodChildTest extends AbstractWithMethodTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Child of AbstractWithMethodTestBase',
      'description' => 'A child class extending a broken base class that is abstract and includes a misplaced test method.',
      'group' => 'Broken tests',
    );
  }

  /**
   * Modules to enable.
   */
  public static $modules = array('broken_tests');

  public function setUp() {
    parent::setUp();
    $this->verbose('Setup executed for AbstractWithMethodChildTest (a normal test class).');
  }

  /**
   * Executes a test.
   */
  function testActualTestMethod() {
    $this->helper();
    $this->verbose('Test method in AbstractWithMethodChildTest executed.');
  }

}
