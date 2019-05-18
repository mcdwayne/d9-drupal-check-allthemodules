<?php

namespace Drupal\passwd_only\Tests;

/**
 * Test the login form.
 *
 * @group passwd_only
 */
class LoginTest extends PasswdOnlyWebTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->userAdminPeople = $this->drupalCreateUser([
      'administer users',
    ]);
  }

  /**
   * Test the login form.
   */
  public function testLogin() {
    $this->configureModule();
    // Go to login page and notice that you already logged in.
    $this->drupalGet('user/passwd-only-login');
    $this->assertText('You are already logged in.');

    // Login with the previously configured account using only the password.
    $this->drupalLogout();
    $this->drupalGet('user/passwd-only-login');
    $this->assertRaw($this->userPasswdOnly->getUsername());
    $this->assertText('Some description text.');
    $this->assertText('Password');
    $edit = [
      'pass' => $this->userPasswdOnly->pass_raw,
    ];
    $this->drupalPostForm(NULL, $edit, t('Log in'));
    $this->assertRaw($this->userPasswdOnly->getUsername());
    $config = \Drupal::config('passwd_only.all');

    $this->assertEqual(
      $config->get('user'),
      $this->userPasswdOnly->id()
    );

    // Test hook_user_view().
    $this->drupalGet('user/' . $this->userPasswdOnly->id());
    $this->assertText('Password Only Login');
    $this->assertText('This user is the password only login account.');

    // Go to profile page of a not configured user.
    $this->drupalLogout();
    $this->drupalLogin($this->userAuthenticated);
    $this->drupalGet('user/' . $this->userAuthenticated->id());
    $this->assertResponse(200);
    $this->assertNoText('Password Only Login');
    $this->assertNoText('This user is the password only login account.');

    // Delete the configured user account.
    $this->config('user.settings')->set('cancel_method', 'user_cancel_delete')->save();
    $this->drupalLogin($this->userAdminPeople);
    $this->drupalPostForm('user/' . $this->userPasswdOnly->id() . '/cancel', NULL, t('Cancel account'));
    $config = \Drupal::config('passwd_only.all');
    $this->assertIdentical($config->get('user'), 0);

    // Access the login page, which is not set up.
    $this->drupalLogout();
    $this->drupalGet('user/passwd-only-login');
    $this->assertResponse(200);
    $this->assertText('First create or set an user account');
  }

}
