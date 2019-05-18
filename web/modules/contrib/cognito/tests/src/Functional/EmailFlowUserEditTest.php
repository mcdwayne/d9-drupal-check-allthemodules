<?php

namespace Drupal\Tests\cognito\Functional;

use Drupal\user\Entity\User;

/**
 * Test editing a users account.
 *
 * @group cognito
 */
class EmailFlowUserEditTest extends CognitoTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'cognito',
    'cognito_tests',
  ];

  /**
   * The user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->user = $this->createExternalUser([
      'administer permissions',
      'administer users',
    ], ['name' => 'Ben']);

    $this->drupalPostForm('/user/login', [
      'mail' => $this->user->getEmail(),
      'pass' => 'letmein',
    ], 'Log in');
  }

  /**
   * Test editing a users account.
   */
  public function testEditUserAccount() {
    $this->drupalGet($this->user->toUrl('edit-form'));
    $this->assertSession()->fieldValueEquals('mail', $this->user->getEmail());
    $this->assertSession()->fieldNotExists('name');

    // Change the users password.
    $this->drupalPostForm($this->user->toUrl('edit-form'), [
      'current_pass' => 'letmein',
      'pass[pass1]' => 'letmein-new',
      'pass[pass2]' => 'letmein-new',
    ], 'Save');

    $this->assertSession()->pageTextContains('Your account has been updated');

    // If they save the form without providing a new password then nothing
    // changes.
    $this->drupalPostForm($this->user->toUrl('edit-form'), [
      'pass[pass1]' => 'letmein-new',
      'pass[pass2]' => 'letmein-new',
    ], 'Save');
  }

  /**
   * Test updating the users email from the UI.
   */
  public function testUpdateUserEmail() {
    // Update the users email address.
    $this->drupalPostForm($this->user->toUrl('edit-form'), [
      'current_pass' => 'letmein',
      'mail' => $new_email = 'new-email@example.com',
    ], 'Save');

    // Re-load the user and ensure the email was updated.
    $account = User::load($this->user->id());
    $this->assertEquals($new_email, $account->getEmail());

    // Ensure the username was kept in sync.
    $this->assertEquals($new_email, $account->getUsername());

    // Ensure that externalauth also has the updated username.
    $this->assertEquals($account->id(), \Drupal::service('externalauth.externalauth')->load($new_email, 'cognito')->id());
  }

}
