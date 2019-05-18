<?php

namespace Drupal\passwd_only\Tests;

use Drupal\user\Entity\User;

/**
 * Test the configuration interface of the module.
 *
 * @group passwd_only
 */
class ConfigurationTest extends PasswdOnlyWebTestBase {

  /**
   * Test the configuration interface of the module.
   */
  public function testConfiguration() {
    $this->drupalLogin($this->userAdminPasswdOnly);

    $this->drupalGet('admin/config/system');
    $this->assertResponse(200);
    $this->assertText('Password Only Login');

    // Main configuration form.
    $this->drupalGet('admin/config/system/passwd-only');
    $this->assertResponse(200);
    $this->assertText('Password Only Login');
    $this->assertText('Select a password only login user');
    $this->assertText('Select a user to login in the password only login forms.');
    $this->assertText('Description');
    $this->assertText('This description text is shown on the password only login form.');

    // Accessed denied for anonymous users.
    $this->drupalLogout();
    $this->drupalGet('admin/config/system/passwd-only');
    $this->assertResponse(403);

    // Access the login page, which is not set up.
    $this->drupalGet('user/passwd-only-login');
    $this->assertResponse(200);
    $this->assertText('First create or set an user account');

    // Configure the module.
    $this->drupalLogin($this->userAdminPasswdOnly);
    $this->drupalGet('admin/config/system/passwd-only');
    // Link to user profile to update the password not showing.
    $this->assertNoText('Change password');
    $this->assertNoText('Go to the password only login user.');
    $edit = [
      'user' => $this->userPasswdOnly->getUsername(),
      'description' => 'Some description text.',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Link to user profile to update the password.
    $this->drupalGet('admin/config/system/passwd-only');
    $this->assertText('Change password');
    $this->assertText('Go to the password only login user.');

    // Try to configure the module with the root user.
    $user_admin = User::load(1);
    $this->drupalLogin($this->userAdminPasswdOnly);
    $edit = [
      'user' => $user_admin->getUsername(),
    ];
    $this->drupalPostForm('admin/config/system/passwd-only', $edit, t('Save'));
    $this->assertResponse(200);
    $this->assertText('is the root user account (User-ID 1). It is not secure to use this account with Password Only Login. Please select another user account.');
    // The input tag gets this classes: class="form-text required error".
    $this->assertRaw('error');
  }

  /**
   * Test the hook “passwd_only_requirements()”.
   */
  public function testRequirements() {
    $user = $this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
      'access site reports',
      'admin passwd only',
    ]);

    $this->drupalLogin($user);
    $this->drupalGet('admin/reports/status');
    $this->assertText('Password Only Login');
    $this->assertText('You have to select one user account.');
    $this->clickLink('Select an user account');
    $this->assertText('Select a user to login in the password only login forms.');

    $this->configureModule();

    $this->drupalLogin($user);
    $this->drupalGet('admin/reports/status');
    $this->assertNoText('You have to select one user account.');
  }

}
