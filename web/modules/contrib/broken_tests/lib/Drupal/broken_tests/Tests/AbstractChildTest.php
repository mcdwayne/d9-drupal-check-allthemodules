<?php

/**
 * @file
 * Definition of Drupal\broken_tests\Tests\AbstractChildTest.
 */

namespace Drupal\broken_tests\Tests;
use Drupal\simpletest\UnitTestBase;

/**
 * Defines a child class extending AbstractTestBase.
 */
class AbstractChildTest extends AbstractTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Child of AbstractTestBase',
      'description' => 'A child class extending a normal abstract base class.',
      'group' => 'Broken tests',
    );
  }

  /**
   * Modules to enable.
   */
  public static $modules = array('broken_tests');

  public function setUp() {
    parent::setUp();
    $this->verbose('Setup executed for AbstractChildTest (a normal test class).');
  }

  /**
   * Executes a test.
   */
  function testActualTestMethod() {
    $this->helper();
    $this->verbose('Test method in AbstractChildTest executed.');
  }

}
