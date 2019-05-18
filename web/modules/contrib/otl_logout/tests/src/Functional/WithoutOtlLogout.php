<?php

namespace Drupal\Tests\otl_logout\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Verify that the standard core functionality works as intended.
 *
 * @group otl_logout
 */
class WithoutOtlLogout extends BrowserTestBase {

  /**
   * The first user object to test with.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account1;

  /**
   * The second user object to test with.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account2;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // Modules for core functionality.
    'node',
    'taxonomy',
    'user',

    // This module is not enabled for this scenario.
    // 'otl_logout',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Step 1: Create two user accounts.
    $this->account1 = $this->drupalCreateUser();
    $this->account2 = $this->drupalCreateUser();

    // Step 2: Log in as user 1.
    $this->drupalGet('/user/login');
    $this->assertResponse(200);
    $this->drupalLogin($this->account1);
    $this->assertResponse(200);
    $this->assertText($this->account1->label());

    // Step 3: Generate the OTL path for user 2.
    $timestamp = REQUEST_TIME;
    $path = 'user/reset/' . $this->account2->id() . "/$timestamp/" . user_pass_rehash($this->account2, $timestamp);

    // Step 4: Load the OTL path.
    $this->drupalGet($path);
  }

  /**
   * Test the results of loading the One Time Login path.
   *
   * With this scenario the request should work but give an error message.
   */
  public function testOTL() {
    // Confirm that the page loaded correctly.
    $this->assertResponse(200);

    // This error message is displayed by UserController::resetPass().
    $this->assertRaw(new FormattableMarkup(
      'Another user (%other_user) is already logged into the site on this computer, but you tried to use a one-time link for user %resetting_user. Please <a href=":logout">log out</a> and try using the link again.',
      [
        '%other_user' => $this->account1->getUsername(),
        '%resetting_user' => $this->account2->getUsername(),
        ':logout' => Url::fromRoute('user.logout')->toString(),
      ]
    ));
  }

}
