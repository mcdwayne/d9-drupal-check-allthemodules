<?php

namespace Drupal\user\Tests;

use Drupal\system\Tests\Cache\PageCacheTagsTestBase;
use Drupal\user\Entity\User;

/**
 * Ensure that password reset methods work as expected.
 *
 * @group user
 */
class TokenLoginTest extends PageCacheTagsTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * This test uses the standard profile to test the password reset in
   * combination with an ajax request provided by the user picture configuration
   * in the standard profile.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * The user object to test password resetting.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('system_menu_block:account');

    // Create a user.
    $account = $this->drupalCreateUser();

    // Activate user by logging in.
    $this->drupalLogin($account);

    $this->account = User::load($account->id());
    $this->account->pass_raw = $account->pass_raw;
    $this->drupalLogout();

    // Set the last login time that is used to generate the one-time link so
    // that it is definitely over a second ago.
    $account->login = \Drupal::time()->getRequestTime() - mt_rand(10, 100000);
    \Drupal::database()->update('users_field_data')
      ->fields(['login' => $account->getLastLoginTime()])
      ->condition('uid', $account->id())
      ->execute();
  }

  /**
   * Tests if the user/login page was successfully changed.
   */
  public function testTokenLoginPage() {

  }

  /**
   * Tests the access control on the password reset page.
   */
  public function testTokenLoginAccessControl() {

  }

  /**
   * Tests the password reset email whitelisting.
   */
  public function testTokenLoginWhitelist() {

  }

  /**
   * Tests password reset functionality.
   */
  public function testUserPasswordReset() {
    // Try to reset the password for an invalid account.
    $this->drupalGet('user/password');

    $edit = ['name' => $this->randomMachineName(32)];
    $this->drupalPostForm(NULL, $edit, t('Submit'));

    $this->assertText(t('@name is not recognized as a username or an email address.', ['@name' => $edit['name']]), 'Validation error message shown when trying to request password for invalid account.');
    $this->assertEqual(count($this->drupalGetMails(['id' => 'user_password_reset'])), 0, 'No email was sent when requesting a password for an invalid account.');

    // Reset the password by username via the password reset page.
    $edit['name'] = $this->account->getUsername();
    $this->drupalPostForm(NULL, $edit, t('Submit'));

    // Verify that the user was sent an email.
    $this->assertMail('to', $this->account->getEmail(), 'Password email sent to user.');
    $subject = t('Replacement login information for @username at @site', ['@username' => $this->account->getUsername(), '@site' => $this->config('system.site')->get('name')]);
    $this->assertMail('subject', $subject, 'Password reset email subject is correct.');

    $reset_url = $this->getResetURL();
    $this->drupalGet($reset_url);
    $this->assertFalse($this->drupalGetHeader('X-Drupal-Cache'));

    // Ensure the password reset URL is not cached.
    $this->drupalGet($reset_url);
    $this->assertFalse($this->drupalGetHeader('X-Drupal-Cache'));

    // Check the one-time login page.
    $this->assertText($this->account->getUsername(), 'One-time login page contains the correct username.');
    $this->assertText(t('This login can be used only once.'), 'Found warning about one-time login.');
    $this->assertTitle(t('Reset password | Drupal'), 'Page title is "Reset password".');

    // Check successful login.
    $this->drupalPostForm(NULL, NULL, t('Log in'));
    $this->assertLink(t('Log out'));
    $this->assertTitle(t('@name | @site', ['@name' => $this->account->getUsername(), '@site' => $this->config('system.site')->get('name')]), 'Logged in using password reset link.');

    // Make sure the ajax request from uploading a user picture does not
    // invalidate the reset token.
    $image = current($this->drupalGetTestFiles('image'));
    $edit = [
      'files[user_picture_0]' => drupal_realpath($image->uri),
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'user_picture_0_upload_button');

    // Change the forgotten password.
    $password = user_password();
    $edit = ['pass[pass1]' => $password, 'pass[pass2]' => $password];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('The changes have been saved.'), 'Forgotten password changed.');

    // Verify that the password reset session has been destroyed.
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t("Your current password is missing or incorrect; it's required to change the Password."), 'Password needed to make profile changes.');

    // Log out, and try to log in again using the same one-time link.
    $this->drupalLogout();
    $this->drupalGet($reset_url);
    $this->assertText(t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'), 'One-time link is no longer valid.');

    // Request a new password again, this time using the email address.
    $this->drupalGet('user/password');
    // Count email messages before to compare with after.
    $before = count($this->drupalGetMails(['id' => 'user_password_reset']));
    $edit = ['name' => $this->account->getEmail()];
    $this->drupalPostForm(NULL, $edit, t('Submit'));
    $this->assertTrue(count($this->drupalGetMails(['id' => 'user_password_reset'])) === $before + 1, 'Email sent when requesting password reset using email address.');

    // Create a password reset link as if the request time was 60 seconds older
    // than the allowed limit.
    $timeout = $this->config('user.settings')->get('password_reset_timeout');
    $bogus_timestamp = \Drupal::time()->getRequestTime() - $timeout - 60;
    $_uid = $this->account->id();
    $this->drupalGet("user/reset/$_uid/$bogus_timestamp/" . user_pass_rehash($this->account, $bogus_timestamp));
    $this->assertText(t('You have tried to use a one-time login link that has expired. Please request a new one using the form below.'), 'Expired password reset request rejected.');

    // Create a user, block the account, and verify that a login link is denied.
    $timestamp = \Drupal::time()->getRequestTime() - 1;
    $blocked_account = $this->drupalCreateUser()->block();
    $blocked_account->save();
    $this->drupalGet("user/reset/" . $blocked_account->id() . "/$timestamp/" . user_pass_rehash($blocked_account, $timestamp));
    $this->assertResponse(403);

    // Verify a blocked user can not request a new password.
    $this->drupalGet('user/password');
    // Count email messages before to compare with after.
    $before = count($this->drupalGetMails(['id' => 'user_password_reset']));
    $edit = ['name' => $blocked_account->getUsername()];
    $this->drupalPostForm(NULL, $edit, t('Submit'));
    $this->assertRaw(t('%name is blocked or has not been activated yet.', ['%name' => $blocked_account->getUsername()]), 'Notified user blocked accounts can not request a new password');
    $this->assertTrue(count($this->drupalGetMails(['id' => 'user_password_reset'])) === $before, 'No email was sent when requesting password reset for a blocked account');

    // Verify a password reset link is invalidated when the user's email address
    // changes.
    $this->drupalGet('user/password');
    $edit = ['name' => $this->account->getUsername()];
    $this->drupalPostForm(NULL, $edit, t('Submit'));
    $old_email_reset_link = $this->getResetUrl();
    $this->account->setEmail("1" . $this->account->getEmail());
    $this->account->save();
    $this->drupalGet($old_email_reset_link);
    $this->assertText(t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'), 'One-time link is no longer valid.');
  }

  /**
   * Retrieves password reset email and extracts the login link.
   */
  public function getResetUrl() {
    // Assume the most recent email.
    $_emails = $this->drupalGetMails();
    $email = end($_emails);
    $urls = [];
    preg_match('#.+user/reset/.+#', $email['body'], $urls);

    return $urls[0];
  }

  /**
   * Test user password reset while logged in.
   */
  public function testUserPasswordResetLoggedIn() {
    // Log in.
    $this->drupalLogin($this->account);

    // Reset the password by username via the password reset page.
    $this->drupalGet('user/password');
    $this->drupalPostForm(NULL, NULL, t('Submit'));

    // Click the reset URL while logged and change our password.
    $reset_url = $this->getResetUrl();
    $this->drupalGet($reset_url);
    $this->drupalPostForm(NULL, NULL, t('Log in'));

    // Change the password.
    $password = user_password();
    $edit = ['pass[pass1]' => $password, 'pass[pass2]' => $password];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('The changes have been saved.'), 'Password changed.');
  }

  /**
   * Prefill the text box on incorrect login via link to password reset page.
   */
  public function testUserResetPasswordTextboxFilled() {
    $this->drupalGet('user/login');
    $edit = [
      'name' => $this->randomMachineName(),
      'pass' => $this->randomMachineName(),
    ];
    $this->drupalPostForm('user/login', $edit, t('Log in'));
    $this->assertRaw(t('Unrecognized username or password. <a href=":password">Have you forgotten your password?</a>',
      [
        ':password' => \Drupal::url(
          'user.pass',
          [],
          ['query' => ['name' => $edit['name']]]
        ),
      ]
    ));
    unset($edit['pass']);
    $this->drupalGet('user/password', ['query' => ['name' => $edit['name']]]);
    $this->assertFieldByName('name', $edit['name'], 'User name found.');
  }

  /**
   * Make sure that users cannot forge password reset URLs of other users.
   */
  public function testResetImpersonation() {
    // Create two identical user accounts except for the user name. They must
    // have the same empty password, so we can't use $this->drupalCreateUser().
    $edit = [];
    $edit['name'] = $this->randomMachineName();
    $edit['mail'] = $edit['name'] . '@example.com';
    $edit['status'] = 1;
    $user1 = User::create($edit);
    $user1->save();

    $edit['name'] = $this->randomMachineName();
    $user2 = User::create($edit);
    $user2->save();

    // Unique password hashes are automatically generated, the only way to
    // change that is to update it directly in the database.
    \Drupal::database()->update('users_field_data')
      ->fields(['pass' => NULL])
      ->condition('uid', [$user1->id(), $user2->id()], 'IN')
      ->execute();
    \Drupal::entityManager()->getStorage('user')->resetCache();
    $user1 = User::load($user1->id());
    $user2 = User::load($user2->id());

    $this->assertEqual($user1->getPassword(), $user2->getPassword(), 'Both users have the same password hash.');

    // The password reset URL must not be valid for the second user when only
    // the user ID is changed in the URL.
    $reset_url = user_pass_reset_url($user1);
    $attack_reset_url = str_replace("user/reset/{$user1->id()}", "user/reset/{$user2->id()}", $reset_url);
    $this->drupalGet($attack_reset_url);
    $this->assertNoText($user2->getUsername(), 'The invalid password reset page does not show the user name.');
    $this->assertUrl('user/password', [], 'The user is redirected to the password reset request page.');
    $this->assertText('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.');
  }

}
