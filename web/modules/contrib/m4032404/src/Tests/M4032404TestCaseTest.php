<?php

namespace Drupal\m4032404\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the functionality of the m4032404 module.
 *
 * @group m4032404
 */
class M4032404TestCaseTest extends WebTestBase {

  /**
   * Privileged user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $privilegedUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['m4032404'];

  /**
   * Setup the default environment settings.
   */
  public function setUp() {
    parent::setUp();

    // Create and log in our privileged user.
    $this->privilegedUser = $this->drupalCreateUser();
    $this->drupalLogin($this->privilegedUser);
  }

  /**
   * Tests 404 Not Found response when hitting /admin.
   */
  public function testM4032404Test404() {
    $this->drupalGet('admin');
    $this->assertResponse(404, 'Anonymous users get a 404 instead of a 403.');
    $this->drupalGet('user/1');
    $this->assertResponse(404, 'User gets a 404 instead of a 403 on non-admin paths.');

    // Set admin-only.
    $this->config('m4032404.settings')->set('admin_only', TRUE)->save();
    $this->drupalGet('user/1');
    $this->assertResponse(403, 'User gets a 403 on non-admin paths when admin-only is configured.');
  }

}
