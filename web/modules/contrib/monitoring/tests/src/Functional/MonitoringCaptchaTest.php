<?php

namespace Drupal\Tests\monitoring\Functional;

/**
 * Tests the captcha failed attempts sensor.
 *
 * @group monitoring
 * @dependencies captcha
 */
class MonitoringCaptchaTest extends MonitoringTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('captcha');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Add captcha.inc file.
    module_load_include('inc', 'captcha');
  }

  /**
   * Tests the captcha failed attempts sensor.
   */
  public function testCaptchaSensor() {

    // Create user and test log in without CAPTCHA.
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
    // Log out again.
    $this->drupalLogout();

    // Set a CAPTCHA on login form.
    captcha_set_form_id_setting('user_login_form', 'captcha/Math');

    // Assert the number of entries in the captcha_session table is 1.
    $this->assertEqual(\Drupal::database()->query('SELECT COUNT (*) FROM {captcha_sessions}')->fetchField(), 0);
    // Try to log in, with invalid captcha answer which should fail.
    $edit = array(
      'name' => $user->getUsername(),
      'pass' => $user->pass_raw,
      'captcha_response' => '?',
    );
    $this->drupalPostForm('user', $edit, t('Log in'));

    // Assert the total number of entries in captcha_sessions table is now 2.
    $this->assertEqual(\Drupal::database()->query('SELECT COUNT (*) FROM {captcha_sessions}')->fetchField(), 1);

    // Run sensor and get the message.
    $message = $this->runSensor('captcha_failed_count')->getMessage();

    // Assert the number of failed attempts.
    $this->assertEqual($message, '1 attempt(s)');
  }

}
