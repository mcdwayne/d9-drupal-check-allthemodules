<?php
/**
 * @file
 * Contains Drupal\securesite\Tests\BasicAuth\SecureSiteBasicGuestUnsetTest
 */
namespace Drupal\securesite\Tests\BasicAuth;

use Drupal\simpletest\WebTestBase;



/**
 * Functional tests for basic authentication with guest credentials unset.
 */
class SecureSiteBasicGuestUnsetTest extends WebTestBase {

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
      'name' => t('Basic authentication: Guest credentials unset'),
      'description' => t('Test HTTP basic authentication with guest credentials unset.'),
      'group' => t('Secure Site'),
    );
  }

  /**
   * Implements setUp().
   */
  function setUp() {
    parent::setUp();
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access secured pages'));
    // Should work with all authentication methods enabled.
    \Drupal::config('securesite.settings')->set('securesite_type', array(SECURESITE_FORM, SECURESITE_BASIC, SECURESITE_DIGEST))->save();
    //todo curl options
    $this->curl_options[CURLOPT_USERPWD] = ':';
  }

  /**
   * Request home page with empty credentials and access disabled.
   */
  function testSecureSiteTypeBasicGuestUnsetEmptyNoAccess() {
    user_role_revoke_permissions(DRUPAL_ANONYMOUS_RID, array('access secured pages'));
    $this->drupalGet(NULL);
    $this->assertResponse(403, t('Requesting home page with empty credentials and guest access disabled.'));
    $this->drupalHead(NULL);
    $this->assertResponse(401, t('Trying to clear credentials by repeating request.'));
  }

  /**
   * Request home page with empty credentials and access enabled.
   */
  function testSecureSiteTypeBasicGuestUnsetEmptyAccess() {
    $this->drupalHead(NULL);
    $this->assertResponse(200, t('Requesting home page with empty credentials and guest access enabled.'));
  }

  /**
   * Request home page with random credentials and access disabled.
   */
  function testSecureSiteTypeBasicGuestUnsetRandomNoAccess() {
    user_role_revoke_permissions(DRUPAL_ANONYMOUS_RID, array('access secured pages'));
    $this->curl_options[CURLOPT_USERPWD] = $this->randomName() . ':' . user_password();
    $this->drupalGet(NULL);
    $this->assertResponse(401, t('Requesting home page with random credentials and guest access disabled.'));
  }

  /**
   * Request home page with random credentials and access enabled.
   */
  function testSecureSiteTypeBasicGuestUnsetRandomAccess() {
    $this->curl_options[CURLOPT_USERPWD] = $this->randomName() . ':' . user_password();
    $this->drupalGet(NULL);
    $this->assertResponse(200, t('Requesting home page with random credentials and guest access enabled.'));
  }

  /**
   * Request home page with credentials for new user.
   */
  function testSecureSiteTypeBasicGuestUnsetChange() {
    $user = $this->drupalCreateUser();
    $this->drupalHead(NULL);
    $this->curl_options[CURLOPT_USERPWD] = "$user->name:$user->pass_raw";
    $this->drupalHead(NULL);
    $this->assertResponse(403, t('Requesting home page with new user credentials.'));
  }

  /**
   * Implements tearDown().
   */
  function tearDown() {
    $this->curl_options = array();
    parent::tearDown();
  }
}