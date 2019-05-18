<?php

/**
 * @file
 * Contains Drupal\securesite\Tests\ForcedAuth\SecureSiteForceAlwaysTest
 */
namespace Drupal\securesite\Tests\ForcedAuth;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for page requests with authentication always forced.
 */
class SecureSiteForceAlwaysTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('securesite');

  protected $user;
  /**
   * Implements getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => t('Forced authentication: Always'),
      'description' => t('Test page requests with authentication always forced.'),
      'group' => t('Secure Site'),
    );
  }

  /**
   * Implements setUp().
   */
  function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser();
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access secured pages'));
    \Drupal::config('securesite.settings')->set('securesite_enabled', SECURESITE_ALWAYS);
  }

  /**
   * Request home page.
   */
  function testSecureSiteForceAlwaysNobody() {
    debug(\Drupal::config('securesite.settings')->get('securesite_enabled'));
    $this->drupalHead(NULL);
    $this->assertResponse(401, t('Requesting home page.'));
  }

  /**
   * Request home page for logged in user.
   */
  function testSecureSiteForceAlwaysUser() {
    $config = \Drupal::config('securesite.settings');
    $config->clear('securesite_enabled')->save();
    $this->drupalLogin($this->user);
    $config->set('securesite_enabled', SECURESITE_ALWAYS)->save();
    $this->drupalHead(NULL);
    $this->assertResponse(200, t('Requesting home page for logged-in user.'));
  }

  /**
   * Request home page for logged in guest.
   */
  function testSecureSiteForceAlwaysGuest() {
    //todo why curl?
    $this->curl_options[CURLOPT_USERPWD] = ':';
    $this->drupalHead(NULL);
    unset($this->curl_options[CURLOPT_USERPWD]);
    $this->curlClose();
    $this->curl_options[CURLOPT_COOKIE] = $this->drupalGetHeader('Set-Cookie');
    $this->drupalHead(NULL);
    $this->assertResponse(200, t('Requesting home page for logged-in guest.'));
  }

  /**
   * Try valid password reset URL.
   */
  function testSecureSiteForceAlwaysResetValid() {
    sleep(1); // Password reset URL must be created at least one second after last log-in.
    $reset = user_pass_reset_url($this->user);
    sleep(1); // Password reset URL must be used at least one second after it is created.
    $this->drupalGet($reset);
    $this->assertResponse(200, t('Trying valid password reset URL.'));
    //todo fix next line
    $this->assertText('This is a one-time login for ' . $this->user->getName() . ' and will expire on', t('Checking for one-time log-in link.'));
  }

  /**
   * Try invalid password reset URL.
   */
  function testSecureSiteForceAlwaysResetInvalid() {
    $this->drupalGet('user/reset/' . $this->user->uid);
    $this->assertResponse(200, t('Trying invalid password reset URL.'));
    $this->assertText('You have tried to use an invalid one-time log-in link.', t('Checking for error message.'));
  }

  /**
   * Submit password reset form.
   */
  function testSecureSiteForceAlwaysResetSubmit() {
    $this->drupalPostForm(NULL, array('name' => $this->user->name), 'E-mail new password');
    $this->assertResponse(200, t('Submitting password reset form.'));
    $this->assertText('Further instructions have been sent to your e-mail address.', t('Checking for password reset message.'));
  }

  /**
   * Try cron.php with all authentication types enabled.
   */
  function testSecureSiteForceAlwaysCronAll() {
    \Drupal::config('securesite.settings')->set('securesite_type', array(SECURESITE_FORM, SECURESITE_BASIC, SECURESITE_DIGEST))->save();
    $cron_last = \Drupal::state()->get('system.cron_last') ?: NULL;
    $this->drupalGet(url(NULL, array('absolute' => TRUE)) . 'cron.php');
    $this->assertTrue((\Drupal::state()->get('system.cron_last') ?: NULL) == $cron_last, t('Trying cron.php with all authentication types enabled.'));
  }

  /**
   * Try cron.php with only form authentication enabled.
   */
  function testSecureSiteForceAlwaysCronForm() {
    \Drupal::config('securesite.settings')->set('securesite_type', array(SECURESITE_FORM))->save();
    $cron_last = \Drupal::state()->get('system.cron_last') ?: NULL;
    $this->drupalGet(url(NULL, array('absolute' => TRUE)) . 'cron.php');
    $this->assertFalse((\Drupal::state()->get('system.cron_last') ?: NULL) == $cron_last, t('Trying cron.php with only form authentication enabled.'));
  }

  /**
   * Implements tearDown().
   */
  function tearDown() {
    $this->curl_options = array();
    parent::tearDown();
  }
}
