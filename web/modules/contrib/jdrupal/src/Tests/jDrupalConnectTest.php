<?php

/**
 * @file
 * jDrupal Connect tests.
 */

namespace Drupal\jdrupal\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the jDrupal Connect resource.
 *
 * @ingroup jdrupal
 * @group jdrupal
 */
class jDrupalConnectTest extends WebTestBase {

  /**
   * Our module dependencies.
   *
   * @var array
   */
  static public $modules = array('jdrupal');

  /**
   * Test anonymous connection.
   */
  public function testAnonymousConnect() {
    // Create a user.
    $test_user = $this->drupalCreateUser(array('access content'));
    // Check that our module did it's thing.
    $this->assertText(t('The test module did its thing.'), "Found evidence of test module.");
  }

  /**
   * Test authenticated connection.
   */
  public function testAuthenticatedConnect() {
    // Create a user.
    $test_user = $this->drupalCreateUser(array('access content'));
    // Log them in.
    $this->drupalLogin($test_user);
    // Check that our module did it's thing.
    $this->assertText(t('The test module did its thing.'), "Found evidence of test module.");
  }

}