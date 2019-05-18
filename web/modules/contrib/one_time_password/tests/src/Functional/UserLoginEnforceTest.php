<?php

namespace Drupal\Tests\one_time_password\Functional;

use Drupal\simpletest\UserCreationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the actual process of logging in with TFA.
 *
 * @coversDefaultClass \Drupal\one_time_password\UserLoginEnforce
 * @group one_time_password
 */
class UserLoginEnforceTest extends BrowserTestBase {

  use UserCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'one_time_password',
  ];

  /**
   * Test the user login with TFA codes.
   */
  public function testUserLogin() {
    // Users without TFA enabled should be able to login as normal.
    $test_user = $this->createUser();
    $this->drupalLogin($test_user);
    $this->drupalLogout();

    // Create a user with TFA enabled.
    $test_user_with_tfa = $this->createUser();
    $test_user_with_tfa->one_time_password->regenerateOneTimePassword();
    $otp_object = $test_user_with_tfa->one_time_password->getOneTimePassword();
    $test_user_with_tfa->save();

    // Attempt to login with a user who has TFA enabled.
    $this->drupalGet('user/login');
    $this->submitForm([
      'name' => $test_user_with_tfa->getUsername(),
      'pass' => $test_user_with_tfa->passRaw,
    ], t('Log in'));
    $this->assertSession()->pageTextContains('The entered two factor authentication code is incorrect.');

    // Attempt to login with an incorrect TFA code.
    $this->drupalGet('user/login');
    $this->submitForm([
      'name' => $test_user_with_tfa->getUsername(),
      'pass' => $test_user_with_tfa->passRaw,
      'one_time_password' => '123456',
    ], t('Log in'));
    $this->assertSession()->pageTextContains('The entered two factor authentication code is incorrect.');

    // Attempt to login with the correct TFA code and ensure the user is
    // correctly logged in.
    $this->drupalGet('user/login');
    $this->submitForm([
      'name' => $test_user_with_tfa->getUsername(),
      'pass' => $test_user_with_tfa->passRaw,
      'one_time_password' => $otp_object->now(),
    ], t('Log in'));
    $test_user_with_tfa->sessionId = $this->getSession()->getCookie($this->getSessionName());
    $this->assertTrue($this->drupalUserIsLoggedIn($test_user_with_tfa), 'User was logged in successfully with a TFA code.');
  }

  /**
   * Test the wider login window works.
   */
  public function testLoginWindow() {
    $test_user_with_tfa = $this->createUser();
    $test_user_with_tfa->one_time_password->regenerateOneTimePassword();
    /** @var \OTPHP\TOTP $otp_object */
    $otp_object = $test_user_with_tfa->one_time_password->getOneTimePassword();
    $test_user_with_tfa->save();

    // Attempt to login with the code that would have been valid about three
    // minutes ago.
    $this->drupalGet('user/login');
    $this->submitForm([
      'name' => $test_user_with_tfa->getUsername(),
      'pass' => $test_user_with_tfa->passRaw,
      'one_time_password' => $otp_object->at(time() - 195),
    ], t('Log in'));

    $test_user_with_tfa->sessionId = $this->getSession()->getCookie($this->getSessionName());
    $this->assertTrue($this->drupalUserIsLoggedIn($test_user_with_tfa), 'User was logged in successfully with a TFA code.');
  }

  /**
   * Test no information disclosure through the flood table.
   */
  public function testFloodTableInformationDisclosure() {
    $this->config('user.flood')
      ->set('user_limit', 3)
      ->save();

    $tfa_user = $this->drupalCreateUser();
    $tfa_user->one_time_password->regenerateOneTimePassword();
    $tfa_user->save();

    // Login 6 times incorrectly to trigger a flood warning from core as well
    // as the OTP threshold.
    foreach (range(0, 5) as $i) {
      $this->drupalPostForm('user/login', [
        'name' => $tfa_user->getUsername(),
        'pass' => 'incorrect password',
      ], 'Log in');
    }

    // Login with the correct password, which flood will deny, but ensure the
    // incorrect TFA code doesn't give us away.
    $this->drupalPostForm('user/login', [
      'name' => $tfa_user->getUsername(),
      'pass' => $tfa_user->passRaw,
      'one_time_password' => '123',
    ], 'Log in');
    $this->assertSession()->pageTextContains('There have been more than 3 failed login attempts');
    // Messages from OTP should not indicate if a password was guessed correctly
    // or not. Verify none of the messages we send are visible.
    $this->assertSession()->pageTextNotContains('The entered two factor authentication code is incorrect.');
    $this->assertSession()->pageTextNotContains('There have been too many incorrect one time passwords entered.');
  }

  /**
   * Test the flood control.
   */
  public function testOtpFloodControl() {
    $tfa_user = $this->drupalCreateUser();
    $tfa_user->one_time_password->regenerateOneTimePassword();
    $tfa_user->save();

    $second_tfa_user = $this->drupalCreateUser();
    $second_tfa_user->one_time_password->regenerateOneTimePassword();
    $second_tfa_user->save();

    // Login enough times with the correct password to trigger a flood control
    // warning from OTP.
    foreach (range(0, 5) as $i) {
      $this->drupalPostForm('user/login', [
        'name' => $tfa_user->getUsername(),
        'pass' => $tfa_user->passRaw,
        'one_time_password' => '123',
      ], 'Log in');
    }
    $this->assertSession()->pageTextContains('There have been too many incorrect one time passwords entered.');

    // Login with a second user to verify there is a per ip address limit.
    $this->drupalPostForm('user/login', [
      'name' => $second_tfa_user->getUsername(),
      'pass' => $second_tfa_user->passRaw,
      'one_time_password' => '123',
    ], 'Log in');
    $this->assertSession()->pageTextContains('There have been too many incorrect one time passwords entered.');

    // Update all flood ip address identifiers and attempt to login with the
    // first user to verify there is a per-user limit.
    \Drupal::database()
      ->update('flood')
      ->condition('event', 'one_time_password.ip')
      ->fields(['identifier' => 'not a real ip'])
      ->execute();
    $this->drupalPostForm('user/login', [
      'name' => $tfa_user->getUsername(),
      'pass' => $tfa_user->passRaw,
      'one_time_password' => '123',
    ], 'Log in');
    $this->assertSession()->pageTextContains('There have been too many incorrect one time passwords entered.');
  }

  /**
   * Test the flood control with repeatedly correct credentials.
   */
  public function testFloodControlWithCorrectCredentials() {
    $tfa_user = $this->drupalCreateUser();
    $tfa_user->one_time_password->regenerateOneTimePassword();
    $tfa_user->save();
    $otp_object = $tfa_user->one_time_password->getOneTimePassword();

    // Login and logout enough times to trigger the flood threshold.
    foreach (range(0, 5) as $i) {
      $this->drupalPostForm('user/login', [
        'name' => $tfa_user->getUsername(),
        'pass' => $tfa_user->passRaw,
        'one_time_password' => $otp_object->now(),
      ], 'Log in');
      $this->drupalLogout();
    }

    // Atempt another valid login and ensure we can get authenticated.
    $this->drupalPostForm('user/login', [
      'name' => $tfa_user->getUsername(),
      'pass' => $tfa_user->passRaw,
      'one_time_password' => $otp_object->now(),
    ], 'Log in');
    $tfa_user->sessionId = $this->getSession()->getCookie($this->getSessionName());
    $this->assertTrue($this->drupalUserIsLoggedIn($tfa_user), 'User was logged in successfully with a TFA code.');
  }

}
