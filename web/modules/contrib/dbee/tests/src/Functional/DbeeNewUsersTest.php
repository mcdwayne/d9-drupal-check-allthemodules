<?php

namespace Drupal\Tests\dbee\Functional;

use Drupal\Core\Url;

/**
 * Add/register users.
 *
 * Test the user registration, 'add a new user' from the admin interface, create
 * new user programmatically, make sure the system do not validate duplicate
 * emails.
 *
 * @group dbee
 */
class DbeeNewUsersTest extends DbeeWebTestBase {

  /**
   * Admin user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUsersAccount;

  /**
   * Existing user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $existingUser;

  /**
   * Account to delete.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $toDeleteAccount;

  /**
   * User storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['dbee'];

  /**
   * Create users with appropriate permissions.
   *
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function setUp() {
    // Enable any modules required for the test.
    parent::setUp();

    // Create a basic user with mail = 'example@example.com'. This email will
    // be used to testing if the system prevent from creating a new user with
    // an existing email.
    // Create a user, with sensitive case mail.
    $this->existingUser = $this->drupalCreateUser();
    // Reload the existing user to encrypt the email address.
    // To reset the user cache, use EntityStorageInterface::resetCache().
    $this->userStorage = $this->container->get('entity_type.manager')
      ->getStorage('user');
    $this->existingUser = $this->userStorage->load($this->existingUser->id());

    $this->adminUsersAccount = $this->drupalCreateUser([
      'administer users',
      'access user profiles',
    ]);
  }

  /**
   * Create users.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testNewUsers() {
    // Create new user form register page, then this new user try to edit it,
    // test sensitive case feature, test for dooblon.
    // Try to create a new user from  the register form.
    $name = $this->randomMachineName();
    $mail = $name . '@exAMple.com';
    $edit1 = [
      'name' => $name,
      'mail' => $mail,
    ];
    $this->drupalPostForm('user/register', $edit1, 'Create new account');
    $session = $this->assertSession();
    // Anonymous user successfully registered.
    $session->responseContains('A welcome message with further instructions has been sent to your email address.');
    /** @var \Drupal\user\UserInterface[] $users */
    $users = $this->userStorage->loadByProperties(['name' => $name]);
    $register_uid = empty($users) ? NULL : reset($users)->id();
    // Test the email address.
    $data_register = [
      $register_uid => [
        'mail' => $mail,
        'init' => $mail,
      ],
    ];
    $this->assertTrue($this->dbeeAllUsersValid($data_register), 'The new user from the register page is correctly encrypted.');

    // Try to create a new user with an email already register, the system
    // should not validate it.
    $edit2 = [
      'name' => $this->randomMachineName(),
      // Set email to 'STRING@EXAMPLE.COM'.
      'mail' => mb_strtoupper($this->existingUser->getEmail()),
    ];
    $this->drupalPostForm('user/register', $edit2, 'Create new account');
    // The module successfully detects when someone trying to register the same
    // email twice, even if there is case conflict.
    $session->pageTextContains("The email address {$edit2['mail']} is already taken");

    // Try to create a new user from the admin interface.
    $this->drupalLogin($this->adminUsersAccount);
    $password3 = user_password();
    $name3 = $this->randomMachineName();
    $mail3 = $name3 . '@example.com';
    $edit3 = [
      'name' => $name3,
      'mail' => $mail3,
      'pass[pass1]' => $password3,
      'pass[pass2]' => $password3,
    ];
    $this->drupalPostForm('admin/people/create', $edit3, 'Create new account');
    // Check if new user account has not been created.
    // From the user_profile_form_submit() function.
    /** @var \Drupal\user\UserInterface[] $users */
    $users = $this->userStorage->loadByProperties(['name' => $name3]);
    $added_uid = empty($users) ? NULL : reset($users)->id();
    $user_link = Url::fromRoute('entity.user.canonical',
      ['user' => $added_uid])->toString();
    // New user successfully added from the admin interface.
    $session->responseContains("Created a new user account for <a href=\"{$user_link}\"><em class=\"placeholder\">{$name3}</em></a>. No email has been sent.");
    // Test the email address.
    $data_added = [
      $added_uid => [
        'mail' => $mail3,
        'init' => $mail3,
      ],
    ];
    $this->assertTrue($this->dbeeAllUsersValid($data_added), 'The new user from the admin interface is correctly encrypted');

    // Try to create a new user with an email already register, the system
    // should not validate it.
    $password4 = user_password();
    $name4 = $this->randomMachineName();
    // Set email to 'STRING@EXAMPLE.COM'.
    $mail4 = mb_strtoupper($this->existingUser->getEmail());
    $edit4 = [
      'name' => $name4,
      'mail' => $mail4,
      'pass[pass1]' => $password4,
      'pass[pass2]' => $password4,
    ];
    $this->drupalPostForm('admin/people/create', $edit4, 'Create new account');
    // Check if new user account has not been created.
    // Trying to add a new user with an existing email displays an error
    // message, even if there is case conflict.
    $session->responseContains("The email address <em class=\"placeholder\">{$mail4}</em> is already taken.");

    // Attempt to bypass duplicate email registration validation by adding
    // spaces.
    $edit['mail'] = '   ' . $mail4 . '   ';

    $this->drupalPostForm('admin/people/create', $edit, 'Create new account');
    // Supplying a duplicate email address with added whitespace displays an
    // error message.
    $session->pageTextContains("The email address {$mail4} is already taken.");

    // Try to save a new user programmatically.
    $password5 = user_password();
    $name5 = $this->randomMachineName();
    $mail5 = $name5 . '@exAMple.com';
    $prog_user_array = [
      'name' => $name5,
    // note: do not md5 the password.
      'pass' => $password5,
      'mail' => $mail5,
      'status' => 1,
      'init' => $mail5,
    ];
    /** @var \Drupal\user\UserInterface $prog_user */
    $prog_user = $this->userStorage->create($prog_user_array);
    $prog_user->save();

    // Test the email address.
    $data_prog = [
      $prog_user->id() => $prog_user_array,
    ];
    $this->assertTrue($this->dbeeAllUsersValid($data_prog), 'The new user from php is correctly encrypted');
    $this->assertEquals($prog_user->getEmail(), $mail5, 'The mail value returned by the user_save() fonction is valid (decrypted)');
    $this->assertEquals($prog_user->getInitialEmail(), $mail5, 'The init value returned by the user_save() fonction is valid (decrypted)');
  }

}
