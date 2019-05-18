<?php

namespace Drupal\Tests\flood_bypass\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group flood_bypass
 */
class UserLoginFloodByPassTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['flood_bypass'];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests that the home page loads with a 200 response.
   */
  public function testUserLoginFloodByPass() {
   $this->config('user.flood')
      // Set a high global limit out so that it is not relevant in the test.
      ->set('ip_limit', 4000)
      ->set('user_limit', 8)
      ->save();

    $user1 = $this->drupalCreateUser([]);
    $incorrect_user1 = clone $user1;
    $incorrect_user1->passRaw .= 'incorrect';

    $user2 = $this->drupalCreateUser([]);

    // Try 8 failed logins.
    for ($i = 0; $i < 8; $i++) {
      $this->assertFailedLogin($incorrect_user1);
    }

    // Try one successful attempt for user 2, it should not trigger any
    // flood control.
    $this->drupalLogin($user1);
    $this->drupalLogout();
  }

  /**
   * Make an unsuccessful login attempt.
   *
   * @param \Drupal\user\Entity\User $account
   *   A user object with name and passRaw attributes for the login attempt.
   * @param mixed $flood_trigger
   *   (optional) Whether or not to expect that the flood control mechanism
   *    will be triggered. Defaults to NULL.
   *   - Set to 'user' to expect a 'too many failed logins error.
   *   - Set to any value to expect an error for too many failed logins per IP
   *   .
   *   - Set to NULL to expect a failed login.
   */
  public function assertFailedLogin($account, $flood_trigger = NULL) {
    $edit = [
      'name' => $account->getUsername(),
      'pass' => $account->passRaw,
    ];
    $this->drupalPostForm('user/login', $edit, t('Log in'));
    $this->assertNoFieldByXPath("//input[@name='pass' and @value!='']", NULL, 'Password value attribute is blank.');
    if (isset($flood_trigger)) {
      if ($flood_trigger == 'user') {
        $this->assertRaw(\Drupal::translation()->formatPlural($this->config('user.flood')->get('user_limit'), 'There has been more than one failed login attempt for this account. It is temporarily blocked. Try again later or <a href=":url">request a new password</a>.', 'There have been more than @count failed login attempts for this account. It is temporarily blocked. Try again later or <a href=":url">request a new password</a>.', [':url' => \Drupal::url('user.pass')]));
      }
      else {
        // No uid, so the limit is IP-based.
        $this->assertRaw(t('Too many failed login attempts from your IP address. This IP address is temporarily blocked. Try again later or <a href=":url">request a new password</a>.', [':url' => \Drupal::url('user.pass')]));
      }
    }
    else {
      $this->assertText(t('Unrecognized username or password. Forgot your password?'));
    }
  }
}
