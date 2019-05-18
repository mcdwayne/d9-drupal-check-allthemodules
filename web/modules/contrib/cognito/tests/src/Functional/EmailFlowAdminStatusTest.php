<?php

namespace Drupal\Tests\cognito\Functional;

/**
 * Test changing the user status as an admin.
 *
 * @group cognito
 */
class EmailFlowAdminStatusTest extends CognitoTestBase {

  /**
   * The user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $userAccount;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->userAccount = $this->createExternalUser();
  }

  /**
   * Test the successful login flow.
   */
  public function testAdminCanToggleUserStatus() {
    $adminUser = $this->createExternalUser([
      'administer permissions',
      'administer users',
      'access user profiles',
    ]);
    $this->drupalPostForm('/user/login', [
      'mail' => $adminUser->getEmail(),
      'pass' => 'letmein',
    ], 'Log in');

    $user_edit_url = $this->userAccount->toUrl('edit-form');

    $this->drupalGet('<front>');
    $this->drupalGet($this->userAccount->toUrl());

    // Block an active user.
    $this->drupalPostForm($user_edit_url, ['status' => 0], 'Save');
    $this->assertSession()->pageTextContains('Account disabled in Cognito');

    // Save an already blocked user and ensure no API calls.
    $this->drupalPostForm($user_edit_url, ['status' => 0], 'Save');
    $this->assertSession()->pageTextNotContains('Account disabled in Cognito');

    // Activate the user.
    $this->drupalPostForm($user_edit_url, ['status' => 1], 'Save');
    $this->assertSession()->pageTextContains('Account enabled in Cognito');

    // Saving an already active user does not make API calls.
    $this->drupalPostForm($user_edit_url, ['status' => 1], 'Save');
    $this->assertSession()->pageTextNotContains('Account enabled in Cognito');
  }

}
