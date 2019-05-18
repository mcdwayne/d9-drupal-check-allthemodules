<?php

namespace Drupal\Tests\legal\Functional;

use Drupal\user\Entity\User;
use Drupal\Core\Test\AssertMailTrait;

/**
 * Tests password reset workflow when T&Cs need to be accepted.
 *
 * @group legal
 */
class PasswordResetTest extends LegalTestBase {

  use AssertMailTrait {
    getMails as drupalGetMails;
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Set the last login time that is used to generate the one-time link so
    // that it is definitely over a second ago.
    $this->account->login = REQUEST_TIME - mt_rand(10, 100000);
    db_update('users_field_data')
      ->fields(['login' => $this->account->getLastLoginTime()])
      ->condition('uid', $this->account->id())
      ->execute();

  }

  /**
   * Test loging in with default Legal seetings.
   */
  public function testPasswordReset() {

    // Reset the password by username via the password reset page.
    $this->drupalGet('user/password');
    $edit['name'] = $this->login_details['name'];
    $this->drupalPostForm(NULL, $edit, t('Submit'));

    // Get one time login URL from email (assume the most recent email).
    $_emails = $this->drupalGetMails();
    $email = end($_emails);
    $urls = [];
    preg_match('#.+user/reset/.+#', $email['body'], $urls);

    // Use one time login URL.
    $this->drupalGet($urls[0]);

    // Log in.
    $this->submitForm([], 'Log in', 'user-pass-reset');

    // Check user is redirected to T&C acceptance page.
    $current_url = $this->getUrl();
    $expected_url = substr($current_url, strlen($this->baseUrl), 45);
    $this->assertEquals($expected_url, '/legal_accept?destination=/user/' . $this->uid . '/edit&token=');
    $this->assertResponse(200);

    // Accept T&Cs and submit form.
    $edit = ['edit-legal-accept' => TRUE];
    $this->submitForm($edit, 'Confirm', 'legal-login');

    // Check user is logged in.
    $account = User::load($this->uid);
    $this->drupalUserIsLoggedIn($account);

    // Check user is redirected to their user page.
    $current_url = $this->getUrl();
    $expected_url = $this->baseUrl . '/user/' . $this->uid . '/edit';
    $this->assertEquals($current_url, $expected_url);
  }

}


