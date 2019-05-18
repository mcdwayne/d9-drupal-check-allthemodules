<?php

namespace Drupal\juicerio\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Ensure that the juicerio content type provided functions properly.
 *
 * The JuicerSimpleTest is a functional test case, meaning that it
 * actually exercises a particular sequence of actions through the web UI.
 * The majority of core test cases are done this way, but the SimpleTest suite
 * also provides unit tests as demonstrated in the unit test case example later
 * in this file.
 *
 * Functional test cases are far slower to execute than unit test cases because
 * they require a complete Drupal install to be done for each test.
 *
 * @see Drupal\simpletest\WebTestBase
 *
 * @ingroup juicerio
 *
 * SimpleTest uses group annotations to help you organize your tests.
 *
 * @group juicerio
 */
class JuicerSimpleTest extends WebTestBase {
  /**
   * Our module dependencies.
   *
   * In Drupal 8's SimpleTest, we declare module dependencies in a public
   * static property called $modules. WebTestBase automatically enables these
   * modules for us.
   *
   * @var array
   */
  static public $modules = array('juicerio');

  /**
   * The installation profile to use with this test.
   *
   * We use the 'minimal' profile so that there are some reasonable default
   * blocks defined, and so we can see the menu link created by our module.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Test Juicer page.
   *
   * Enable Juicer and see if it can return its main
   * page. Should return a 403.
   */
  public function testJuicerAnonConfigFormMenu() {
    // Verify that anonymous cannot access the config page.
    $this->drupalGet('admin/config/services/juicerio');
    $this->assertResponse(403, 'Anon users cannot access the config page.');
  }

  /**
   * Test Juicer page.
   *
   * Enable Juicer and see if it can successfully return its main
   * page.
   */
  public function testJuicerConfigFormMenu() {
    // Verify that authenticated users with correct perms can access the config page.
    // Create a user.
    $test_user = $this->drupalCreateUser(array('administer juicer'));
    // Log them in.
    $this->drupalLogin($test_user);

    $this->drupalGet('admin/config/services/juicerio');
    $this->assertResponse(200, 'Valid user can access the config page.');
  }
}