<?php
/**
 * @file
 * Contains Drupal\securesite\Tests\FormAuth\SecureSiteFormGuestTest
 */

namespace Drupal\securesite\Tests\FormAuth;

use Drupal\simpletest\WebTestBase;


/**
 * Functional tests for form authentication with guest credentials.
 */
class SecureSiteFormGuestTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('securesite');

  /**
   * Implements getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => t('Form authentication: Guest credentials'),
      'description' => t('Test HTML form authentication with guest credentials.'),
      'group' => t('Secure Site'),
    );
  }

  /**
   * Implements setUp().
   */
  function setUp() {
    parent::setUp();
    $config = \Drupal::config('securesite.settings');
    $config->set('securesite_enabled', SECURESITE_ALWAYS);
    // Should work with all authentication methods enabled.
    $config->set('securesite_type', array(SECURESITE_FORM, SECURESITE_BASIC, SECURESITE_DIGEST));
    $config->save();
  }

  /**
   * Request home page with empty credentials and access disabled.
   */
  function testSecureSiteTypeFormGuestUnsetEmptyNoAccess() {
    $this->drupalPostForm('', array('name' => '', 'pass' => ''), 'Log in');
    $this->assertNoFieldByXPath('//form[@id="securesite-user-login"]', '', t('Requesting home page with empty credentials and guest access disabled.'));
    $this->assertText('Anonymous users are not allowed to log in to secured pages.', t('Checking for access denied message when guest access is disabled and credentials are empty.'));
  }

  /**
   * Request home page with random credentials and access disabled.
   */
  function testSecureSiteTypeFormGuestUnsetRandomNoAccess() {
    $this->drupalPostForm('', array('name' => $this->randomName(), 'pass' => user_password()), 'Log in');
    $this->assertFieldByXPath('//form[@id="securesite-user-login"]', '', t('Requesting home page with random credentials and guest access disabled.'));
    $this->assertText('Unrecognized user name and/or password.', t('Checking for error message when guest access is disabled and random password is given.'));
  }

  /**
   * Request home page with random credentials and access enabled.
   */
  function testSecureSiteTypeFormGuestUnsetRandomAccess() {
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access secured pages'));
    $this->drupalPostForm('', array('name' => $this->randomName(), 'pass' => user_password()), 'Log in');
    $this->assertNoFieldByXPath('//form[@id="securesite-user-login"]', '', t('Requesting home page with random credentials and guest access enabled.'));
    $this->assertFieldByXPath('//form[@id="user-login-form"]', '', t('Checking for user log-in form when guest access is enabled and random password is given.'));
  }
}

