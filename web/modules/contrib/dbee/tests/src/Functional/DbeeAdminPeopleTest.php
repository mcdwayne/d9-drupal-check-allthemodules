<?php

namespace Drupal\Tests\dbee\Functional;

/**
 * Search a user from admin/people page.
 *
 * Searching from email and name.
 *
 * @group dbee
 */
class DbeeAdminPeopleTest extends DbeeWebTestBase {

  /**
   * User with different name than the one in the mail.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * A user that should not appear on research.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser2;

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable Do not enable the dbee module yet.
   *
   * @var array
   */
  protected static $modules = ['views', 'dbee'];

  /**
   * Create users with appropriate permissions.
   *
   * {@inheritdoc}
   */
  public function setUp() {
    // Enable any modules required for the test.
    parent::setUp();
    $this->testUser = $this->drupalCreateUser();
    $this->testUser->setUsername($this->randomMachineName())->save();
    $this->adminUser = $this->drupalCreateUser(['administer users']);
    $this->testUser2 = $this->drupalCreateUser();
  }

  /**
   * Search users from Admin People page.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testAdminPeople() {
    $this->drupalLogin($this->adminUser);
    $session = $this->assertSession();
    $this->drupalGet('admin/people');
    $session->responseContains($this->testUser->getAccountName());
    $session->responseContains($this->testUser2->getAccountName());

    $edit1 = ['user' => mb_strtoupper($this->testUser->getEmail())];
    $this->drupalPostForm('admin/people', $edit1, 'Filter');
    // The searching of the user by completed email address succeeds.
    $session->responseContains($this->testUser->getAccountName());
    $session->responseNotContains($this->testUser2->getAccountName());

    $edit2 = ['user' => mb_strtoupper($this->testUser->getAccountName())];
    $this->drupalPostForm('admin/people', $edit2, 'Filter');
    // The searching of the user by name succeeds.
    $this->assertNoUniqueText($this->testUser->getAccountName());
    //$session->responseContains($this->testUser->getAccountName());
    $session->responseNotContains($this->testUser2->getAccountName());
  }

}
