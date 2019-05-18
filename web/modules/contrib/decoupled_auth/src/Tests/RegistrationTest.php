<?php

namespace Drupal\decoupled_auth\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests User registration with and without Acquisition.
 *
 * @group decoupled_auth
 */
class RegistrationTest extends WebTestBase {

  use DecoupledAuthUserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['decoupled_auth', 'user', 'system'];

  /**
   * The user settings config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $userConfig;

  /**
   * The acquisition settings config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $acquisitionConfig;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->userConfig = $this->config('user.settings');
    $this->acquisitionConfig = $this->config('decoupled_auth.settings');
  }

  /**
   * Disable acquisition on registration.
   */
  protected function disableRegistrationAcquisition() {
    $this->acquisitionConfig->set('acquisitions.registration', 0)->save();
  }

  /**
   * Post user information to user register form.
   *
   * @param string $name
   *   The username of the user.
   * @param string $mail_prefix
   *   The mail prefix prior to the '@' of the email.
   *
   * @return array
   *   Array of information that was used to create user.
   */
  protected function registerNewUser($name = '', $mail_prefix = '') {
    $edit = [];
    $edit['name'] = $name ? $name : $this->randomMachineName();
    $edit['mail'] = $mail_prefix ? $mail_prefix . '@example.com' : $edit['name'] . '@example.com';

    $this->drupalPostForm('user/register', $edit, t('Create new account'));

    return $edit;
  }

  /**
   * Load User objects by an entity property.
   *
   * @param array $property
   *   An array of a property value keyed by the property.
   *
   * @return array
   *   An array of User entity objects indexed by their ids.
   */
  protected function getUsersByProperty(array $property) {
    $user_storage = $this->container->get('entity_type.manager')->getStorage('user');
    $accounts = $user_storage->loadByProperties($property);
    return $accounts;
  }

  /**
   * Test the standard registration process when there is no existing user.
   */
  public function testNormalNone() {
    // Set up test environment configuration.
    $this->disableRegistrationAcquisition();

    // Test registering a new user when there are no existing users.
    // Expected result: create a new user.
    $edit = $this->registerNewUser();

    // Load created user and check properties.
    $accounts = $this->getUsersByProperty(['name' => $edit['name']]);

    if (empty($accounts)) {
      $this->fail('No User accounts loaded.');
    }
    else {
      $account = reset($accounts);
      $this->assertTrue($account->isActive());
      $this->assertEqual($edit['name'], $account->getUsername());
    }
  }

  /**
   * Test the acquisition registration process when there is no existing user.
   */
  public function testAcquisitionNone() {
    // Test registering a new user when there are no existing users.
    // Expected result: create a new user.
    $edit = $this->registerNewUser();
    // @TODO There is no confirmation message when new decoupled user is created.

    // Load created user and check properties.
    $accounts = $this->getUsersByProperty(['name' => $edit['name']]);

    if (empty($accounts)) {
      $this->fail('No User accounts loaded.');
    }
    else {
      $account = reset($accounts);
      $this->assertTrue($account->isActive());
      $this->assertEqual($edit['name'], $account->getUsername());
    }
  }

  /**
   * Test the normal registration process when there is a single existing user.
   */
  public function testNormalSingle() {
    // Set up test environment configuration.
    $this->disableRegistrationAcquisition();
    // Allow all decoupled users to have non-unique emails.
    $this->acquisitionConfig->set('unique_emails.mode', 'none')->save();

    // Test registering a new user when the single existing user is decoupled.
    // Expected result: create a new user.
    $user_1 = $this->createDecoupledUser();
    $email = $user_1->email_prefix . '@example.com';
    $edit = $this->registerNewUser('', $user_1->email_prefix);

    // Load created user and check properties.
    $accounts = $this->getUsersByProperty(['name' => $edit['name']]);

    if (empty($accounts)) {
      $this->fail('No User accounts loaded.');
    }
    else {
      $account = reset($accounts);
      $this->assertTrue($account->isActive());
      $this->assertEqual($email, $account->getEmail());

      // Check that user is not decoupled.
      $this->assertEqual($edit['name'], $account->getUsername());
      $this->assertNotEqual($user_1->id(), $account->id());
    }

    // Test registering a new user when the single existing user is coupled.
    // Expected result: fail with validation error.
    $name = $email_prefix = $this->randomMachineName();
    $this->drupalCreateUser([], $name);
    $this->registerNewUser('', $email_prefix);
    $this->assertText('The email address ' . $email_prefix . '@example.com is already taken.');
  }

  /**
   * Test acquisition registration when there is a single existing user.
   */
  public function testAcquisitionSingle() {
    // Test registering a new user when the single existing user is decoupled.
    // Expected result: User should acquire the existing user.
    $user_1 = $this->createDecoupledUser();
    $email = $user_1->email_prefix . '@example.com';

    $edit = $this->registerNewUser('', $user_1->email_prefix);

    // Load created user and check properties.
    $accounts = $this->getUsersByProperty(['name' => $edit['name']]);

    if (empty($accounts)) {
      $this->fail('No User accounts loaded.');
    }
    else {
      $account = reset($accounts);
      $this->assertTrue($account->isActive());
      $this->assertEqual($email, $account->getEmail());
      $this->assertEqual($edit['name'], $account->getUsername());
      $this->assertEqual($user_1->id(), $account->id());
    }

    // Test registering a new user when the single existing user is coupled.
    // Expected result: fail with validation error.
    $name = $email_prefix = $this->randomMachineName();
    $this->drupalCreateUser([], $name);
    $this->registerNewUser('', $email_prefix);
    $this->assertText('The email address ' . $email_prefix . '@example.com is already taken.');
  }

  /**
   * Test normal registration when there is are multiple existing users.
   */
  public function testNormalMultiple() {
    // Set up test environment configuration.
    $this->disableRegistrationAcquisition();
    // Allow all decoupled users to have non-unique emails.
    $this->acquisitionConfig->set('unique_emails.mode', 'none')->save();

    // Test registering a new user when all existing users are decoupled.
    // Expected result: create a new user.
    $user_1 = $this->createDecoupledUser();
    $user_2 = $this->createDecoupledUser($user_1->email_prefix);
    $email = $user_1->email_prefix . '@example.com';

    $edit = $this->registerNewUser('', $user_1->email_prefix);

    // Load created user and check properties.
    $accounts = $this->getUsersByProperty(['name' => $edit['name']]);

    if (empty($accounts)) {
      $this->fail('No User accounts loaded.');
    }
    else {
      $account = reset($accounts);
      $this->assertTrue($account->isActive());
      $this->assertEqual($email, $account->getEmail());
      $this->assertNotEqual($user_1->id(), $account->id());
      $this->assertNotEqual($user_2->id(), $account->id());
    }

    // Test registering a new user when one existing user is coupled.
    // Expected result: fail with validation error.
    $this->registerNewUser('', $user_1->email_prefix);
    $this->assertText('The email address ' . $user_1->email_prefix . '@example.com is already taken.');
  }

  /**
   * Test acquisition registration when there is are multiple existing users.
   */
  public function testAcquisitionMultiple() {
    // Allow all decoupled users to have non-unique emails.
    $this->acquisitionConfig->set('unique_emails.mode', 'none')->save();

    // Test registering a new user when all existing users are decoupled.
    // Expected result: create a new user.
    $user_1 = $this->createDecoupledUser();
    $user_2 = $this->createDecoupledUser($user_1->email_prefix);
    $email = $user_1->email_prefix . '@example.com';

    $edit = $this->registerNewUser('', $user_1->email_prefix);

    // Load created user and check properties.
    $accounts = $this->getUsersByProperty(['name' => $edit['name']]);

    if (empty($accounts)) {
      $this->fail('No User accounts loaded.');
    }
    else {
      $account = reset($accounts);
      $this->assertTrue($account->isActive());
      $this->assertEqual($email, $account->getEmail());
      $this->assertNotEqual($user_1->id(), $account->id());
      $this->assertNotEqual($user_2->id(), $account->id());
    }

    // Test registering a new user when one existing user is coupled.
    // Expected result: fail with validation error.
    $this->registerNewUser('', $user_1->email_prefix);
    $this->assertText('The email address ' . $user_1->email_prefix . '@example.com is already taken.');
  }

  /**
   * Test the acquisition registration process.
   *
   * In this test there is are multiple existing users and we are acquiring
   * the first.
   */
  public function testAcquisitionMultipleFirst() {
    // Change the site config to acquire the first.
    $this->acquisitionConfig->set('acquisitions.behavior_first', 1)->save();
    // Allow all decoupled users to have non-unique emails.
    $this->acquisitionConfig->set('unique_emails.mode', 'none')->save();

    // Test registering a new user when all existing users are decoupled.
    // Expected result: User should acquire the first existing user.
    $user_1 = $this->createDecoupledUser();
    $user_2 = $this->createDecoupledUser($user_1->email_prefix);
    $user_3 = $this->createDecoupledUser($user_1->email_prefix);
    $email = $user_1->email_prefix . '@example.com';

    $edit = $this->registerNewUser('', $user_1->email_prefix);
    // @TODO There is no confirmation message when new decoupled user is created.

    // Load created user and check properties.
    $accounts = $this->getUsersByProperty(['name' => $edit['name']]);

    if (empty($accounts)) {
      $this->fail('No User accounts loaded.');
    }
    else {
      $account = reset($accounts);
      $this->assertTrue($account->isActive());
      $this->assertEqual($email, $account->getEmail());

      // Make sure that new user has acquired one of the decoupled users.
      $user_ids = [$user_1->id(), $user_2->id(), $user_3->id()];
      $this->assertTrue(in_array($account->id(), $user_ids));
    }

    // Test registering a new user when one existing user is coupled.
    // Expected result: fail with validation error.
    $this->registerNewUser('', $user_1->email_prefix);
    $this->assertText('The email address ' . $user_1->email_prefix . '@example.com is already taken.');
  }

}
