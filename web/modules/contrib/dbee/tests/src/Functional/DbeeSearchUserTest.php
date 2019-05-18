<?php

namespace Drupal\Tests\dbee\Functional;

use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Search users by email.
 *
 * Ensure searching user via email address is still available. Try first without
 * the dbee module, then with the dbee module enabled.
 *
 * @group dbee
 */
class DbeeSearchUserTest extends DbeeWebSwitchTestBase {

  /**
   * Existing user 1.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $existingUser1;

  /**
   * Existing user 2.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $existingUser2;

  /**
   * Existing user 3.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $existingUser3;

  /**
   * Provider.
   *
   * @var string
   */
  protected $provider = 'MYprovider.com';

  /**
   * Search user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $searchUsersAccount;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'search'];

  /**
   * Create users with appropriate permissions.
   *
   * {@inheritdoc}
   */
  public function setUp() {
    // Enable any modules required for the test.
    parent::setUp();

    // Create a user, with sensitive case mail.
    $this->existingUser1 = $this->drupalCreateUser();
    // Make sure the email and name are distincts.
    $this->existingUser1->setUsername('anyname1')
      ->setEmail($this->randomMachineName() . '@' . $this->provider)
      ->save();

    $this->existingUser2 = $this->drupalCreateUser();
    // Make sure the email and name are distincts.
    $this->existingUser2->setUsername('anyname2')
      ->setEmail($this->randomMachineName() . '@' . $this->provider)
      ->save();

    // Extra user, do nothing.
    $this->existingUser3 = $this->drupalCreateUser();

    // Create a user who can search by email address and log in.
    $this->searchUsersAccount = $this->drupalCreateUser([
      'administer users',
      'search content',
      'access user profiles',
    ]);
    // Create a user who can enable the dbee module.
    $this->adminModulesAccount = $this->drupalCreateUser(['administer modules']);
  }

  /**
   * Serach user with and without dbee module.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSearchUser() {
    // Test the feature : search user using an existing email address : first
    // without the dbee module, then with dbee module enable.
    $this->searchUsers();
    // Enable the dbee module.
    $this->dbeeEnablingDisablingDbeeModule(TRUE);
    // Search again.
    $this->searchUsers();
  }

  /**
   * Searchs users, save queries and results.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function searchUsers() {
    $this->drupalLogin($this->searchUsersAccount);
    // Search the completed email address.
    $edit1 = ['keys' => mb_strtoupper($this->existingUser1->getEmail())];
    $this->drupalPostForm('search/user', $edit1, 'Search');
    $session = $this->assertSession();
    // The searching of the user by completed email address succeeds.
    $label1 = $this->existingUser1->getAccountName() . ' (' . $this->existingUser1->getEmail() . ')';
    $session->linkExists($label1);

    // Search the partial email address.
    $edit2 = ['keys' => mb_strtoupper($this->provider)];
    $this->drupalPostForm('search/user', $edit2, 'Search');
    // The searching of the user by partial email address succeeds
    // (first user found).
    $session->linkExists($label1);

    $label2 = $this->existingUser2->getAccountName() . ' (' . $this->existingUser2->getEmail() . ')';
    // The searching of the user by partial email address succeeds
    // (the second user found).
    $session->linkExists($label2);

    $label3 = $this->existingUser3->getAccountName() . ' (' . $this->existingUser3->getEmail() . ')';
    // The searching of the user by partial email address succeed
    // (the third user not found).
    $session->linkNotExists($label3);
  }

}
