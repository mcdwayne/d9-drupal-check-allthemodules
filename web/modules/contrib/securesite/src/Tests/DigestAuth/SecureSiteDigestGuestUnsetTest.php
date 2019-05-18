<?php

/**
 * @file
 * Contains Drupal\securesite\Tests\DigestAuth\SecureSiteDigestGuestUnsetTest
 */
namespace Drupal\securesite\Tests\DigestAuth;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for digest authentication with guest credentials unset.
 */
class SecureSiteDigestGuestUnsetTest extends WebTestBase {

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
      'name' => t('Digest authentication: Guest credentials unset'),
      'description' => t('Test HTTP digest authentication with guest credentials unset. Digest scripts must be configured on the live site before these tests can be run.'),
      'group' => t('Secure Site'),
    );
  }

  /**
   * Implements setUp().
   */
  function setUp() {
    parent::setUp();
    //todo wtf is this function?
    _securesite_copy_script_config($this);
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access secured pages'));
    $config = \Drupal::config('securesite.settings');
    $config->set('securesite_enabled', SECURESITE_ALWAYS);
    // Should work with all authentication methods enabled.
    $config->set('securesite_type', array(SECURESITE_FORM, SECURESITE_BASIC, SECURESITE_DIGEST));
    $config->save();
    //todo curl options
    $this->curl_options[CURLOPT_HTTPAUTH] = CURLAUTH_DIGEST;
  }

  /**
   * Request home page with empty credentials.
   */
  function testSecureSiteTypeDigestGuestUnsetEmpty() {
    $this->curl_options[CURLOPT_USERPWD] = ':';
    $this->drupalHead(NULL);
    $this->assertResponse(200, t('Requesting home page with empty credentials.'));
    $this->assertFalse($this->drupalGetHeader('Authentication-Info'), t('Checking digest authentication bypass for empty guest credentials.'));
  }

  /**
   * Request home page with random credentials.
   */
  function testSecureSiteTypeDigestGuestUnsetRandom() {
    $this->curl_options[CURLOPT_USERPWD] = $this->randomName() . ':' . user_password();
    $this->drupalHead(NULL);
    $this->assertResponse(200, t('Requesting home page with random credentials.'));
    $this->assertFalse($this->drupalGetHeader('Authentication-Info'), t('Checking digest authentication bypass for random guest credentials.'));
  }
}
