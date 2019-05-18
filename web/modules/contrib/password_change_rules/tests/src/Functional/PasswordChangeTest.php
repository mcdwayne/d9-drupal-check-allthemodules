<?php

namespace Drupal\Tests\password_change_rules\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the Password Change Rules module.
 *
 * @group password_change_rules
 */
class PasswordChangeTest extends BrowserTestBase {

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
   * The normal user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->admin = $this->createUser([
      'administer users',
      'force users to change password',
      'administer password change rules',
    ]);
    $this->user = $this->createUser();
  }

  public function testUserRegister() {
    // Admin registered account global setting should not be taken into account
    // for registering.
    $this->configUpdate('admin_registered_account', TRUE);

    // Require email verification.
    $config = $this->config('user.settings');
    $config->set('register', 'visitors_admin_approval')->save();
    $config->set('verify_mail', FALSE)->save();

    $username = $this->randomMachineName();
    $mail = $username . '@example.com';
    $pass = user_password();
    $this->drupalPostForm('/user/register', [
      'name' => $username,
      'mail' => $mail,
      'pass[pass1]' => $pass,
      'pass[pass2]' => $pass,
    ], 'Create new account');

    $accounts = $this->container->get('entity_type.manager')->getStorage('user')->loadByProperties(['mail' => $mail]);
    $account = reset($accounts);

    $this->assertEquals(0, $account->password_change_rules->value, 'User who set password should not be required to change it.');
  }

  /**
   * Test the permissions for users who can access the change password field.
   */
  public function testPermissions() {
    // Admin with the correct permission can view the force password change.
    $this->drupalLogin($this->admin);
    $url = Url::fromRoute('entity.user.edit_form', ['user' => $this->admin->id()]);
    $this->drupalGet($url);
    $this->assertSession()->fieldExists('password_change_rules[value]');

    // User cannot view the force password change on their own account.
    $this->drupalLogin($this->user);
    $url = Url::fromRoute('entity.user.edit_form', ['user' => $this->user->id()]);
    $this->drupalGet($url);
    $this->assertSession()->fieldNotExists('password_change_rules[value]');

    // Admin with administer useres but not the special permission cannot see
    // the change password field.
    $admin2 = $this->createUser(['administer users']);
    $this->drupalLogin($admin2);
    $url = Url::fromRoute('entity.user.edit_form', ['user' => $this->user->id()]);
    $this->drupalGet($url);
    $this->assertSession()->fieldNotExists('password_change_rules[value]');
  }

  /**
   * Ensure that we can manually force new users to change their password.
   */
  public function testAdminCreatingNewUser() {
    $this->drupalLogin($this->admin);
    $mail = $this->randomMachineName() . '@example.com';
    $original_password = 'admin-set-password';
    $this->drupalPostForm(Url::fromRoute('user.admin_create'), [
      'mail' => $mail,
      'name' => $mail,
      'pass[pass1]' => $original_password,
      'pass[pass2]' => $original_password,
      'password_change_rules[value]' => TRUE,
    ], 'Create new account');

    $this->drupalLogout();
    $this->drupalPostForm(Url::fromRoute('user.login'), ['name' => $mail, 'pass' => $original_password], 'Log in');
    // @TODO, remove this...
    $this->drupalGet($this->getUrl() . '/edit');

    // Ensure we're told to change our password.
    $this->assertSession()->pageTextContains($this->getPasswordChangeMessage());

    // Attempt to change it to the same password.
    $this->drupalPostForm(NULL, [
      'current_pass' => $original_password,
      'pass[pass1]' => $original_password,
      'pass[pass2]' => $original_password,
    ], 'Save');
    $this->assertSession()->pageTextContains('You must change your password to something new');

    $this->drupalPostForm(NULL, [
      'current_pass' => $original_password,
      'pass[pass1]' => 'new-password',
      'pass[pass2]' => 'new-password',
    ], 'Save');
    $this->assertSession()->pageTextNotContains($this->getPasswordChangeMessage());
  }

  /**
   * Ensure we can force existing users to change their password.
   */
  public function testResetExistingUser() {
    $this->drupalLogin($this->admin);
    $original_password = 'admin-set-password';

    // Reset the password as an admin and then force the user to change their
    // password.
    $url = Url::fromRoute('entity.user.edit_form', ['user' => $this->user->id()]);
    $this->drupalPostForm($url, [
      'pass[pass1]' => $original_password,
      'pass[pass2]' => $original_password,
      'password_change_rules[value]' => TRUE,
    ], 'Save');

    // Login as the user and ensure we're required to reset our password.
    $this->drupalLogout();
    $this->drupalPostForm(Url::fromRoute('user.login'), ['name' => $this->user->getAccountName(), 'pass' => $original_password], 'Log in');
    $this->assertSession()->pageTextContains($this->getPasswordChangeMessage());

    // Update our account and the password notification will disappear.
    $this->drupalPostForm(NULL, [
      'current_pass' => $original_password,
      'pass[pass1]' => 'new-password',
      'pass[pass2]' => 'new-password',
    ], 'Save');
    $this->assertSession()->pageTextNotContains($this->getPasswordChangeMessage());
  }

  /**
   * When admin registered account is forced, then they must always reset.
   */
  public function testAdminRegisteredAccountSetting() {
    $original_password = 'original-password';

    // Enable the admin registered account setting.
    $this->configUpdate('admin_registered_account', TRUE);
    $this->drupalLogin($this->admin);
    $this->drupalGet(Url::fromRoute('user.admin_create'));

    // The field will not exist because it's enforced globally.
    $this->assertSession()->fieldNotExists('password_change_rules[value]');

    $mail = $this->randomMachineName() . '@example.com';
    $this->drupalPostForm(Url::fromRoute('user.admin_create'), [
      'mail' => $mail,
      'name' => $mail,
      'pass[pass1]' => $original_password,
      'pass[pass2]' => $original_password,
    ], 'Create new account');

    $this->drupalLogout();
    $this->drupalPostForm(Url::fromRoute('user.login'), [
      'name' => $mail,
      'pass' => $original_password,
    ], 'Log in');
    $this->assertSession()->pageTextContains($this->getPasswordChangeMessage());
  }

  /**
   * Ensure the global option works when an admin changes a users password.
   */
  public function testAdminChangedPassword() {
    $doTest = function () {
      // Login as the admin and change the users password.
      $this->drupalLogin($this->admin);
      $url = Url::fromRoute('entity.user.edit_form', ['user' => $this->user->id()]);
      $this->drupalPostForm($url, [
        'pass[pass1]' => 'new-password',
        'pass[pass2]' => 'new-password',
      ], 'Save');

      // Login as the user.
      $this->drupalLogout();
      $this->drupalPostForm(Url::fromRoute('user.login'), [
        'name' => $this->user->getAccountName(),
        'pass' => 'new-password',
      ], 'Log in');
    };

    // Changing the users password by default does not have any impact.
    $doTest();
    $this->assertSession()->pageTextNotContains($this->getPasswordChangeMessage());

    // Enforce the admin changed password option globally, the user will now be
    // forced to change their password.
    $this->configUpdate('admin_change_password', TRUE);
    $this->drupalLogout();
    $doTest();
    $this->assertSession()->pageTextContains($this->getPasswordChangeMessage());
  }

  public function testOneTimeLogin() {
    $this->user->password_change_rules = TRUE;
    $this->user->save();
    $url = user_pass_reset_url($this->user);
    $this->drupalGet($url);
    $this->drupalPostForm(NULL, [], 'Log in');

    // We should not be required to enter our current password.
    $this->assertSession()->pageTextNotContains('Current password');
    $this->assertSession()->fieldNotExists('current_pass');
  }

  /**
   * Gets the configure password change message.
   *
   * @return string
   *   The change message.
   */
  protected function getPasswordChangeMessage() {
    return $this->container->get('config.factory')
      ->get('password_change_rules.settings')
      ->get('change_password_message');
  }

  /**
   * Helper to update our own settings.
   *
   * @param string $key
   *   The config to update.
   * @param mixed $value
   *   The config value.
   */
  protected function configUpdate($key, $value) {
    $this->container->get('config.factory')
      ->getEditable('password_change_rules.settings')
      ->set($key, $value)
      ->save();
  }

}
