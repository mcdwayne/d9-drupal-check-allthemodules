<?php
/**
 * @file
 * Contains Drupal\securesite\Tests\FormAuth\SecureSiteFormUserTest
 */

namespace Drupal\securesite\Tests\FormAuth;

use Drupal\simpletest\WebTestBase;


/**
 * Functional tests for form authentication with user credentials.
 */
class SecureSiteFormUserTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('securesite');

  //todo phpdoc comments
  protected $normal_user;

  protected $access_user;

  /**
   * Implements getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => t('Form authentication: User credentials'),
      'description' => t('Test HTML form authentication with user credentials.'),
      'group' => t('Secure Site'),
    );
  }

  /**
   * Implements setUp().
   */
  function setUp() {
    parent::setUp();
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access secured pages'));
    $this->normal_user = $this->drupalCreateUser();
    $this->access_user = $this->drupalCreateUser(array('access secured pages'));
    $config = \Drupal::config('securesite.settings');
    $config->set('securesite_enabled', SECURESITE_ALWAYS);
    // Should work with all authentication methods enabled.
    $config->set('securesite_type', array(SECURESITE_FORM, SECURESITE_BASIC, SECURESITE_DIGEST));
    $config->save();
  }

  /**
   * Request home page with wrong password.
   */
  function testSecureSiteTypeFormUserWrong() {
    $this->drupalPostForm('', array('name' => $this->access_user->name, 'pass' => $this->access_user->pass), 'Log in');
    $this->assertFieldByXPath('//form[@id="securesite-user-login"]', '', t('Requesting home page with wrong password.'));
    $this->assertText('Unrecognized user name and/or password.', t('Checking for error message when password is wrong.'));
  }

  /**
   * Request home page with correct password and access disabled.
   */
  function testSecureSiteTypeFormUserNoAccess() {
    $this->drupalPostForm('', array('name' => $this->normal_user->name, 'pass' => $this->normal_user->pass_raw), 'Log in');
    $this->assertNoFieldByXPath('//form[@id="securesite-user-login"]', '', t('Requesting home page with correct password and access disabled.'));
    $this->assertText('You have not been authorized to log in to secured pages.', t('Checking for access denied message when password is correct and access is disabled.'));
  }

  /**
   * Request home page with correct password and access enabled.
   */
  function testSecureSiteTypeFormUserAccess() {
    $this->drupalPostForm('', array('name' => $this->access_user->name, 'pass' => $this->access_user->pass_raw), 'Log in');
    $this->assertNoFieldByXPath('//form[@id="securesite-user-login"]', '', t('Requesting home page with correct password and access enabled.'));
    $this->assertText($this->access_user->name, t('Checking for user name when password is correct and access is enabled.'));
    $this->assertText('My account', t('Checking for account link when password is correct and access is enabled.'));
    $this->assertText('Log out', t('Checking for log-out link when password is correct and access is enabled.'));
  }
}
