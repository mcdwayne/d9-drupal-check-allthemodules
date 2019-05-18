<?php

/**
 * @file
 * Definition of Drupal\broken_tests\Tests\GetInfolessTest.
 */

namespace Drupal\broken_tests\Tests;
use Drupal\simpletest\UnitTestBase;

/**
 * Defines a test class that is missing its getInfo() method.
 */
class GetInfolessTest extends AbstractTestBase {

  /**
   * Modules to enable.
   */
  public static $modules = array('broken_tests');

  public function setUp() {
    parent::setUp();
    $this->verbose('Setup executed for GetInfolessTest (a broken test class missing its getInfo() method).');
  }

  /**
   * Executes a test.
   */
  function testActualTestMethod() {
    $this->helper();
    $this->verbose('Test method in AbstractChildTest executed.');
  }

}
