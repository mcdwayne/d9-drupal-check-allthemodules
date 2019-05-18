<?php

namespace Drupal\disable_user_1_edit\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the functionality of the disable_user_1_edit module.
 *
 * @group disable_user_1_edit
 */
class DisableUser1EditTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['disable_user_1_edit'];

  /**
   * A test user with permission to administer users.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer users',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test that things work as expected.
   */
  public function testAccessDenied() {
    $this->drupalGet('/user/1/edit');
    $this->assertResponse(403);
    // Also make sure we can disable it.
    \Drupal::configFactory()
      ->getEditable('disable_user_1_edit.settings')
      ->set('disabled', 1)
      ->save();
    $this->drupalGet('/user/1/edit');
    $this->assertResponse(200);
  }

}
