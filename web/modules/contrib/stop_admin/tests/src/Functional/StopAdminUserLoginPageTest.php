<?php

namespace Drupal\Tests\stop_admin\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class StopAdminUserLoginPageTest.
 *
 * @package Drupal\Tests\stop_admin\Functional
 * @group stop_admin
 */
class StopAdminUserLoginPageTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['stop_admin'];

  /**
   * A non-administrator user for this test.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $regularUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create a regular user and activate it.
    $this->regularUser = $this->drupalCreateUser();
    $this->drupalLogin($this->regularUser);
    $this->drupalLogout();
  }

  /**
   * Test the user login page as user 1.
   */
  public function testUserLoginPageUserOne() {
    $this->drupalGet('user/login');
    $edit = [
      'name' => $this->rootUser->getAccountName(),
      'pass' => $this->rootUser->passRaw,
    ];
    $this->drupalPostForm('user/login', $edit, t('Log in'));
    $this->assertTrue(strpos($this->getSession()->getPage()->getContent(), (string) t('Log in')) > 0, 'User is not logged in.');
    $this->assertSession()->pageTextContains('Unrecognized username or password. Forgot your password?', 'Error message appeared.');
  }

  /**
   * Test the user login page as an authenticated user.
   */
  public function testUserLoginPageUserAuthenticated() {
    $this->drupalGet('user/login');
    $edit = [
      'name' => $this->regularUser->getAccountName(),
      'pass' => $this->regularUser->passRaw,
    ];
    $this->drupalPostForm('user/login', $edit, t('Log in'));
    $this->assertFalse(strpos($this->getSession()->getPage()->getContent(), (string) t('Log in')) > 0, 'User is logged in.');
    $this->assertSession()->pageTextNotContains('Unrecognized username or password. Forgot your password?', 'Error message did not appear.');
  }

  /**
   * Test the user login page as user 1 when the setting is set to disabled.
   */
  public function testStopAdminDisabledUserLoginPageUserOne() {
    // Disable the setting.
    \Drupal::configFactory()
      ->getEditable('stop_admin.settings')
      ->set('disabled', TRUE)
      ->save(TRUE);
    // Test to make sure we can login as user 1.
    $edit = [
      'name' => $this->rootUser->getAccountName(),
      'pass' => $this->rootUser->passRaw,
    ];
    $this->drupalPostForm('user/login', $edit, t('Log in'));
    $this->assertFalse(strpos($this->getSession()->getPage()->getContent(), (string) t('Log in')) > 0, 'User is logged in.');
    $this->assertSession()->pageTextNotContains('Unrecognized username or password. Forgot your password?', 'Error message did not appear.');
  }

}
