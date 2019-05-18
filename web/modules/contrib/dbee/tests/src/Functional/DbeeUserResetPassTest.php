<?php

namespace Drupal\Tests\dbee\Functional;

/**
 * User reset password from email.
 *
 * Test sending email for lost password user.
 *
 * @group dbee
 */
class DbeeUserResetPassTest extends DbeeWebTestBase {

  /**
   * Edited user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editedUserAccount;

  /**
   * Existing user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $existingUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['dbee'];

  /**
   * Create users with appropriate permissions.
   *
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Create a basic user with mail = 'example@example.com'. This email will
    // be used to testing if the system prevent from creating a new user with
    // an existing email.
    // Create a user, with sensitive case mail.
    $this->existingUser = $this->drupalCreateUser();

    $this->editedUserAccount = $this->drupalCreateUser();
    // Make sure the mail is sensitive case.
    $this->editedUserAccount->setEmail($this->editedUserAccount->getAccountName() . '@eXample.com')
    // drupalCreateUser() set an empty 'init' value. Fix it.
      ->set('init', $this->randomMachineName() . '@example.com')
      ->save();
    // Make sure we are logged out.
  }

  /**
   * Test sending pasword to user.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testUserPass() {

    $uid = $this->editedUserAccount->id();

    $data0 = [
      $uid => [
        'mail' => $this->editedUserAccount->getEmail(),
        'init' => $this->editedUserAccount->getInitialEmail(),
      ],
    ];
    $this->assertTrue($this->dbeeAllUsersValid($data0), 'The user is encrypted and can be decrypted back');
    // Go to the lost password page.
    $this->drupalGet('user/password');
    // Set the email sensitive case.
    $edit1 = [
      'name' => $data0[$uid]['mail'],
    ];
    $this->drupalPostForm('user/password', $edit1, 'Submit');
    $session = $this->assertSession();
    // Login link successfully sent for the exact sensitive case email.
    $session->pageTextContains('Further instructions have been sent to your email address.');

    $this->drupalGet('user/password');
    // Set the email to lowercase.
    $edit2 = [
      'name' => mb_strtolower($data0[$uid]['mail']),
    ];
    $this->drupalPostForm('user/password', $edit2, 'Submit');
    // Login link successfully sent for lowercase email.
    $session->pageTextContains('Further instructions have been sent to your email address.');

    // Set the email to uppercase.
    $edit3 = [
      'name' => mb_strtoupper($data0[$uid]['mail']),
    ];
    $this->drupalPostForm('user/password', $edit3, 'Submit');
    // Login link successfully sent for other sensitive case email.
    $session->pageTextContains('Further instructions have been sent to your email address.');

    $edit4 = [
      'name' => $this->editedUserAccount->getAccountName(),
    ];
    $this->drupalPostForm('user/password', $edit4, 'Submit');
    // Login link successfully sent for username.
    $session->pageTextContains('Further instructions have been sent to your email address.');
  }

}
