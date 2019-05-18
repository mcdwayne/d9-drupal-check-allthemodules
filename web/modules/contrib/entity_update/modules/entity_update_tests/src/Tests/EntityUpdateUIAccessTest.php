<?php

namespace Drupal\entity_update_tests\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test install and uninstall Entity Update module.
 *
 * @group Entity Update
 */
class EntityUpdateUIAccessTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_update', 'entity_update_tests'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'administer software updates',
    ];

    // A simple user without any specific permissions.
    $this->user = $this->drupalCreateUser([]);
    // User to set up entity_update.
    $this->admin_user = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests Pages for Anonymous users.
   */
  public function testAnonymousAccess() {

    // Check home page.
    $this->drupalGet('');
    $this->assertResponse(200, 'Page :  Site homepage');

    // Run tests.
    $this->runPageAccess(403);
  }

  /**
   * Tests Pages for Simple users.
   */
  public function testSimpleUserAccess() {

    // Simple user login.
    $this->drupalLogin($this->user);

    // Check home page.
    $this->drupalGet('');
    $this->assertResponse(200, 'Page :  Site homepage');

    // Run tests.
    $this->runPageAccess(403);
  }

  /**
   * Tests Pages for admin user.
   */
  public function testAdminsAccess() {
    // Admin user login.
    $this->drupalLogin($this->admin_user);

    // Run tests.
    $this->runPageAccess(200);
  }

  /**
   * Run page tests.
   */
  private function runPageAccess($code = NULL) {

    // Return if NULL.
    if (!$code) {
      return;
    }

    $this->drupalGet('admin/config/development/entity-update');
    $this->assertResponse($code, "Page :  Root ($code)");

    $this->drupalGet('admin/config/development/entity-update/tests');
    $this->assertResponse($code, "Page :  Test entity fields settings page ($code)");

    $this->drupalGet('admin/config/development/entity-update/exec');
    $this->assertResponse($code, "Page :  Entity update exec page ($code)");

    $this->drupalGet('admin/config/development/entity-update/types');
    $this->assertResponse($code, "Page :  Entity types ($code)");

    $this->drupalGet('admin/config/development/entity-update/status');
    $this->assertResponse($code, "Page :  Entity types update status ($code)");

    $this->drupalGet('admin/config/development/entity-update/list');
    $this->assertResponse($code, "Page :  Entity list ($code)");

    $this->drupalGet('admin/config/development/entity-update/list/user/1');
    $this->assertResponse($code, "Page :  Entity list of user/1 ($code)");

    $this->drupalGet('admin/config/development/entity-update/list/user/1/2');
    $this->assertResponse($code, "Page :  Entity list of user/1/2 ($code)");
  }

}
