<?php

namespace Drupal\Tests\sharedemail\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the sharedemail module.
 *
 * @group sharedemail
 */
class SharedEmailTest extends BrowserTestBase {

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'sharedemail',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser([
      'administer users',
      'administer site configuration',
      'administer shared email',
      'create shared email account',
      'access shared email message',
    ]);
  }

  /**
   * Test the configuration form.
   */
  public function testUpdateMessage() {

    $this->drupalLogin($this->user);

    $this->drupalGet('admin/config/people/shared-email');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals(
      'sharedemail_msg',
      $this->config('sharedemail.settings')->get('sharedemail_msg')
    );

    $edit = [
      'sharedemail_msg' => 'Test message',
    ];

    // Post the form.
    $this->drupalPostForm('admin/config/people/shared-email', $edit, t('Save configuration'));

    // Test the new values are there.
    $this->drupalGet('admin/config/people/shared-email');

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals('sharedemail_msg', 'Test message');
  }

  /**
   * Test that a non-duplicate email does not display the warning message.
   */
  public function testNonDuplicateEmail() {

    $this->drupalLogin($this->user);

    $this->config('user.settings')
      ->set('verify_mail', FALSE)
      ->set('register', USER_REGISTER_VISITORS)
      ->save();

    // Set up a user to check for duplicates.
    $duplicateUser = $this->drupalCreateUser();

    $edit = [
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . $duplicateUser->getEmail(),
      'pass[pass1]' => 'Test1Password',
      'pass[pass2]' => 'Test1Password',
    ];

    // Attempt to create a new account using a unique email address.
    $this->drupalPostForm('admin/people/create', $edit, t('Create new account'));

    $this->assertSession()->pageTextContains(t('Created a new user account for @name. No email has been sent.', ['@name' => $edit['name']]));
    $this->assertSession()->pageTextNotContains($this->config('sharedemail.settings')->get('sharedemail_msg'));
  }

  /**
   * Test that a user w/o sufficient permission cannot duplicate email address.
   */
  public function testCannotDuplicateEmail() {

    $notAllowedUser = $this->drupalCreateUser([
      'administer users',
    ]);

    $this->drupalLogin($notAllowedUser);

    // Set up a user to check for duplicates.
    $duplicateUser = $this->drupalCreateUser();

    $edit = [
      'name' => $this->randomMachineName(),
      'mail' => $duplicateUser->getEmail(),
      'pass[pass1]' => 'Test1Password',
      'pass[pass2]' => 'Test1Password',
    ];

    // Attempt to create a new account using an existing email address.
    $this->drupalPostForm('admin/people/create', $edit, t('Create new account'));

    $this->assertSession()->pageTextContains(t('The email address @email is already taken.', ['@email' => $duplicateUser->getEmail()]));
  }

  /**
   * Test that a user with sufficient permission can duplicate email address.
   */
  public function testCanDuplicateEmail() {

    $this->drupalLogin($this->user);

    // Set up a user to check for duplicates.
    $duplicateUser = $this->drupalCreateUser();

    $edit = [
      'name' => $this->randomMachineName(),
      'mail' => $duplicateUser->getEmail(),
      'pass[pass1]' => 'Test1Password',
      'pass[pass2]' => 'Test1Password',
    ];

    // Attempt to create a new account using an existing email address.
    $this->drupalPostForm('admin/people/create', $edit, t('Create new account'));

    $this->assertSession()->pageTextContains(t('Created a new user account for @name. No email has been sent.', ['@name' => $edit['name']]));
  }

  /**
   * Test email duplication for allowed email addresses.
   */
  public function testNotAllowedDuplicateEmail() {

    $this->drupalLogin($this->user);

    // Set up a user to check for duplicates.
    $duplicateUser = $this->drupalCreateUser();

    $this->config('sharedemail.settings')
      ->set('sharedemail_allowed', $this->randomMachineName())
      ->save();

    $edit = [
      'name' => $this->randomMachineName(),
      'mail' => $duplicateUser->getEmail(),
      'pass[pass1]' => 'Test1Password',
      'pass[pass2]' => 'Test1Password',
    ];

    // Attempt to create a new account using an existing email address.
    $this->drupalPostForm('admin/people/create', $edit, t('Create new account'));

    $this->assertSession()->pageTextContains(t('The email address @email is already taken.', ['@email' => $duplicateUser->getEmail()]));
  }

  /**
   * Test email duplication for allowed email addresses.
   */
  public function testAllowedDuplicateEmail() {

    $this->drupalLogin($this->user);

    $this->config('user.settings')
      ->set('verify_mail', FALSE)
      ->set('register', USER_REGISTER_VISITORS)
      ->save();

    // Set up a user to check for duplicates.
    $duplicateUser = $this->drupalCreateUser();

    $this->config('sharedemail.settings')
      ->set('sharedemail_allowed', $duplicateUser->getEmail())
      ->save();

    $edit = [
      'name' => $this->randomMachineName(),
      'mail' => $duplicateUser->getEmail(),
      'pass[pass1]' => 'Test1Password',
      'pass[pass2]' => 'Test1Password',
    ];

    // Attempt to create a new account using an existing email address.
    $this->drupalPostForm('admin/people/create', $edit, t('Create new account'));

    $this->assertSession()->pageTextContains(t('Created a new user account for @name. No email has been sent.', ['@name' => $edit['name']]));
  }

  /**
   * Test that a duplicate email is allowed with message.
   */
  public function testDuplicateEmailWithMessage() {

    $this->drupalLogin($this->user);

    $this->config('user.settings')
      ->set('verify_mail', FALSE)
      ->set('register', USER_REGISTER_VISITORS)
      ->save();

    // Set up a user to check for duplicates.
    $duplicateUser = $this->drupalCreateUser();

    $edit = [
      'name' => $this->randomMachineName(),
      'mail' => $duplicateUser->getEmail(),
      'pass[pass1]' => 'Test1Password',
      'pass[pass2]' => 'Test1Password',
    ];

    // Attempt to create a new account using an existing email address.
    $this->drupalPostForm('admin/people/create', $edit, t('Create new account'));

    $this->assertSession()->pageTextContains(t('Created a new user account for @name. No email has been sent.', ['@name' => $edit['name']]));
    $this->assertSession()->pageTextContains($this->config('sharedemail.settings')->get('sharedemail_msg'));
  }

  /**
   * Test allowed duplicate email, but w/o access to the message.
   */
  public function testDuplicateEmailWithoutMessage() {

    $noMessageUser = $this->drupalCreateUser([
      'administer users',
      'administer shared email',
      'create shared email account',
    ]);

    $this->drupalLogin($noMessageUser);

    $this->config('user.settings')
      ->set('verify_mail', FALSE)
      ->set('register', USER_REGISTER_VISITORS)
      ->save();

    // Set up a user to check for duplicates.
    $duplicateUser = $this->drupalCreateUser();

    $edit = [
      'name' => $this->randomMachineName(),
      'mail' => $duplicateUser->getEmail(),
      'pass[pass1]' => 'Test1Password',
      'pass[pass2]' => 'Test1Password',
    ];

    // Attempt to create a new account using an existing email address.
    $this->drupalPostForm('admin/people/create', $edit, t('Create new account'));

    $this->assertSession()->pageTextContains(t('Created a new user account for @name. No email has been sent.', ['@name' => $edit['name']]));
    $this->assertSession()->pageTextNotContains($this->config('sharedemail.settings')->get('sharedemail_msg'));
  }

}
