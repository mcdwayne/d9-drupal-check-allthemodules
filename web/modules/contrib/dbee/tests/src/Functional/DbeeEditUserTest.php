<?php

namespace Drupal\Tests\dbee\Functional;

/**
 * Edit users.
 *
 * Test the user mail edition.
 *
 * @group dbee
 */
class DbeeEditUserTest extends DbeeWebTestBase {

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
    // drupalCreateUser() set an empty 'init' value. Fix it.
    $this->editedUserAccount->set('init', $this->randomMachineName() . '@example.com')
      ->save();

  }

  /**
   * Test user edition.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testEditUser() {

    $this->drupalLogin($this->editedUserAccount);
    $uid = $this->editedUserAccount->id();
    $pass = $this->editedUserAccount->pass_raw;

    $data0 = [
      $uid => [
        'mail' => $this->editedUserAccount->getEmail(),
        'init' => $this->editedUserAccount->getInitialEmail(),
      ],
    ];
    $this->assertTrue($this->dbeeAllUsersValid($data0), 'The user is correctly encrypted.');
    // Check if the email is decrypted on the user edit page.
    $this->drupalGet('user/' . $uid . '/edit');
    $session = $this->assertSession();
    // The email on the user edit page is available.
    $session->fieldValueEquals('mail', $data0[$uid]['mail']);

    // Set the email to lowercase.
    $edit1 = [
      'mail' => mb_strtolower($data0[$uid]['mail']),
      'current_pass' => $pass,
    ];
    $this->drupalPostForm('user/' . $uid . '/edit', $edit1, 'Save');
    $data1 = $data0;
    $data1[$uid]['mail'] = $edit1['mail'];
    $this->assertTrue($this->dbeeAllUsersValid($data1), 'The user mail is correctly encrypted.');

    // Check is email is still encryped after mail edit, Check case sensitive.
    // Email address :
    $edit3 = [
      'mail' => $this->randomMachineName() . '@EXAMple.com',
      'current_pass' => $pass,
    ];
    $this->drupalPostForm('user/' . $uid . '/edit', $edit3, 'Save');
    // Check user account on edition.
    // We successfully edit the user email address :
    // to a new case sensitive email address:
    $session->pageTextContains('The changes have been saved.');
    // Check if the stored email is encrypted.
    $data3 = $data1;
    $data3[$uid]['mail'] = $edit3['mail'];
    $this->assertTrue($this->dbeeAllUsersValid($data3), 'The user mail is correctly encrypted.');
    // Check if the email is decrypted on the user edit page.
    $this->drupalGet('user/' . $uid . '/edit');
    // Email on the user edit page is still available
    // and case sensitive is respected.
    $session->fieldValueEquals('mail', $edit3['mail']);

    // Check is email is still encryped after mail edit, back to a new
    // lowercase email address :
    $edit4 = [
      'mail' => mb_strtolower($this->randomMachineName() . '@example.com'),
      'current_pass' => $pass,
    ];
    $this->drupalPostForm('user/' . $uid . '/edit', $edit4, 'Save');
    // Check user account on edition.
    // We successfully edit the user email address again:
    // back to a new lower case email address:
    $session->pageTextContains('The changes have been saved.');
    // Check if the stored email is encrypted.
    $data4 = $data3;
    $data4[$uid]['mail'] = $edit4['mail'];
    $this->assertTrue($this->dbeeAllUsersValid($data4), 'The user mail  is correctly encrypted.');
    // Check if the email is decrypted on the user edit page.
    $this->drupalGet('user/' . $uid . '/edit');
    // The email on the user edit page is still available.
    $session->fieldValueEquals('mail', $edit4['mail']);

    // Try to change the email address to an existing one from another user. The
    // system should not validate it.
    $edit5 = [
      'mail' => mb_strtoupper($this->existingUser->getEmail()),
      'current_pass' => $pass,
    ];
    $this->drupalPostForm('user/' . $uid . '/edit', $edit5, 'Save');
    // Check if new user account has not been created.
    // From the user_account_form_validate() function.
    // The system successfully detects when someone trying to save the same
    // email twice, even if there is case conflict.
    $session->pageTextContains("The email address {$edit5['mail']} is already taken.");
    // Check if the stored email is encrypted.
    $this->assertTrue($this->dbeeAllUsersValid($data4), 'The user informations have not changed');
  }

}
