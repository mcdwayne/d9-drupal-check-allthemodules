<?php

namespace Drupal\Tests\password_change_rules\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the Password Change Rules admin UI.
 *
 * @group password_change_rules
 */
class PasswordChangeAdminUi extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['password_change_rules'];

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $admin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->admin = $this->createUser([
      'administer password change rules',
    ]);
    $this->drupalLogin($this->admin);
  }

  /**
   * Test the admin UI.
   */
  public function testAdminUi() {
    $session = $this->assertSession();

    // Visit form, assert access.
    $this->drupalGet(Url::fromRoute('password_change_rules.admin_form'));

    // Assert the defaults.
    $session->fieldValueEquals('change_password_message', 'An administrator has requested that you change your password. Please update your password to continue.');
    $session->checkboxNotChecked('admin_registered_account');
    $session->checkboxNotChecked('admin_change_password');

    // Change settings and ensure they're saved.
    $this->drupalPostForm(NULL, [
      'change_password_message' => 'Test message',
      'admin_registered_account' => '1',
      'admin_change_password' => '1',
    ], 'Save configuration');

    // Test with user who shouldn't have access and assert denied.
    $this->assertSession()->fieldValueEquals('change_password_message', 'Test message');
    $session->checkboxChecked('admin_registered_account');
    $session->checkboxChecked('admin_change_password');
  }

}
