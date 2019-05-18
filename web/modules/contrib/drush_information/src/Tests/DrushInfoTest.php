<?php

namespace Drupal\drush_info\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests drush_info access control.
 *
 * @group drush_info
 */
class DrushInfoTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['drush_info'];

  /**
   * Make sure the user cannot access the page.
   */
  protected function testNonAdminUser() {
    $this->drupalLogin($this->drupalCreateUser());

    $this->drupalGet('admin/structure/drush-info');
    $this->assertResponse(403);
  }

  /**
   * Log in as a root user and check to make sure we can access the page.
   */
  protected function testAdminUser() {
    $this->drupalLogin($this->rootUser);

    $this->drupalGet('admin/structure/drush-info');
    $this->assertResponse(200);
  }

}
