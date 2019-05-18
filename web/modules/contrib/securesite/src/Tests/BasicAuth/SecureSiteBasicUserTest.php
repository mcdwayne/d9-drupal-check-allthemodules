<?php
/**
 * @file
 * Contains Drupal\securesite\Tests\BasicAuth\SecureSiteBasicUserTest
 */
namespace Drupal\securesite\Tests\BasicAuth;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for basic authentication with user credentials.
 */
class SecureSiteBasicUserTest extends WebTestBase {

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
      'name' => t('Basic authentication: User credentials'),
      'description' => t('Test HTTP basic authentication with user credentials.'),
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
    // Should work with all authentication methods enabled.
    \Drupal::config('securesite.settings')->set('securesite_type', array(SECURESITE_FORM, SECURESITE_BASIC, SECURESITE_DIGEST))->save();
  }

  /**
   * Request home page with wrong password.
   */
  function testSecureSiteTypeBasicUserWrong() {
    //todo - curl options
    $this->curl_options[CURLOPT_USERPWD] = $this->access_user->name . ':' . $this->access_user->pass;
    $this->drupalHead(NULL);
    $this->assertResponse(401, t('Requesting home page with wrong password.'));
  }

  /**
   * Request home page with correct password and access disabled.
   */
  function testSecureSiteTypeBasicUserNoAccess() {
    $this->curl_options[CURLOPT_USERPWD] = $this->normal_user->name . ':' . $this->normal_user->pass_raw;
    $this->drupalGet(NULL);
    $this->assertResponse(403, t('Requesting home page with correct password and access disabled.'));
    $this->drupalHead(NULL);
    $this->assertResponse(401, t('Trying to clear credentials by repeating request.'));
  }

  /**
   * Request home page with correct password and access enabled.
   */
  function testSecureSiteTypeBasicUserAccess() {
    $this->curl_options[CURLOPT_USERPWD] = $this->access_user->name . ':' . $this->access_user->pass_raw;
    $this->drupalGet(NULL);
    $this->assertResponse(200, t('Requesting home page with correct password and access enabled.'));
    $this->assertText($this->access_user->name, t('Checking for user name when password is correct and access is enabled.'));
    $this->assertText('My account', t('Checking for account link when password is correct and access is enabled.'));
    $this->assertText('Log out', t('Checking for log-out link when password is correct and access is enabled.'));
    $this->drupalHead('user/logout');
    $this->assertResponse(401, t('Requesting log-out page.'));
  }

  /**
   * Request home page with credentials for new user.
   */
  function testSecureSiteTypeBasicUserChange() {
    $this->drupalLogin($this->drupalCreateUser());
    $this->curl_options[CURLOPT_USERPWD] = $this->access_user->name . ':' . $this->access_user->pass_raw;
    $this->drupalGet(NULL);
    $this->assertResponse(200, t('Requesting home page with credentials for new user.'));
    $this->assertText($this->access_user->name, t('Checking for new user name on page.'));
  }

  /**
   * Implements tearDown().
   */
  function tearDown() {
    $this->curl_options = array();
    parent::tearDown();
  }
}