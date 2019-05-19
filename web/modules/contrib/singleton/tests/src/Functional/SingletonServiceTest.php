<?php

namespace Drupal\Tests\singleton\Functional;

use Drupal\singleton_test\SingletonTest;
use Drupal\Tests\BrowserTestBase;

/**
 * Test to make sure the Drupal service pulls in Singleton class.
 */
class SingletonServiceTest extends BrowserTestBase {

  public static $modules = ['singleton_test'];

  /**
   * Test to make sure the Drupal service pulls in Singleton class.
   */
  public function testFooBar() {
    // Test to make sure the Drupal service pulls in Singleton class.
    // To do this, we will invoke a method called foo to make sure
    // nothing crashes (no exceptions thrown).
    $test_instance = SingletonTest::getInstance();
    $this->assertEquals('bar', $test_instance->foo());
  }

}
