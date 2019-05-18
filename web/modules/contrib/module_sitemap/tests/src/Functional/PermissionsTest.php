<?php

namespace Drupal\module_sitemap\Tests;

use Drupal\Tests\module_sitemap\Functional\FunctionalTestBase;

/**
 * Tests module_sitemap access control.
 *
 * @group module_sitemap
 */
class PermissionsTest extends FunctionalTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['module_sitemap'];

  /**
   * Test the admin page as well as the sitemap page.
   */
  public function testUnauthorizedUser() {
    // Log in as an authenticated drupal user so we can test the admin page.
    $this->drupalLogin($this->drupalCreateUser());

    // Test Sitemap functionality.
    $this->drupalGet('module-sitemap');
    $this->assertSession()->statusCodeEquals(403);

    // Make sure the configuration page exists. User should be denied access.
    $this->drupalGet('admin/config/development/module-sitemap');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Log in as a user with the 'access module sitemap' permission.
   */
  public function testAuthorizedUser() {
    // Users with the 'access module sitemap' permission should be able to
    // access the module sitemap page but not administer the module.
    $this->drupalLogin($this->drupalCreateUser(['access module sitemap']));

    // Test Sitemap functionality.
    $this->drupalGet('module-sitemap');
    $this->assertSession()->statusCodeEquals(200);

    // Make sure that this user cannot see any admin links. If they can, fail
    // the test.
    $this->assertSession()->responseNotContains('Administer');

    // Make sure the configuration page exists. User should be denied access.
    $this->drupalGet('admin/config/development/module-sitemap');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Log in as a user with the 'administer module sitemap' permission.
   */
  public function testAdminUser() {
    $this->drupalLogin($this->drupalCreateUser(['access module sitemap', 'administer module sitemap']));

    // Test Sitemap functionality.
    $this->drupalGet('module-sitemap');
    $this->assertSession()->statusCodeEquals(200);

    // Make sure the configuration page exists. User should be granted access.
    $this->drupalGet('admin/config/development/module-sitemap');
    $this->assertSession()->statusCodeEquals(200);
  }

}
