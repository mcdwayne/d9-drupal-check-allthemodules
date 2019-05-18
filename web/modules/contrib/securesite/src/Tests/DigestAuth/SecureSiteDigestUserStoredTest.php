<?php

/**
 * @file
 * Contains Drupal\securesite\Tests\DigestAuth\SecureSiteDigestUserStoredTest
 */
namespace Drupal\securesite\Tests\DigestAuth;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for digest authentication with user credentials.
 */
class SecureSiteDigestUserStoredTest extends WebTestBase {

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
      'name' => t('Digest authentication: User credentials stored'),
      'description' => t('Test HTTP digest authentication with stored user credentials. Digest scripts must be configured on the live site before these tests can be run.'),
      'group' => t('Secure Site'),
    );
  }

  /**
   * Implements setUp().
   */
  function setUp() {
    parent::setUp();
    //todo wtf is this function
    _securesite_copy_script_config($this);
    $config = \Drupal::config('securesite.settings');
    $config->set('securesite_enabled', SECURESITE_ALWAYS);
    // Should work with all authentication methods enabled.
    $config->set('securesite_type', array(SECURESITE_FORM, SECURESITE_BASIC, SECURESITE_DIGEST));
    $config->save();
    $this->user = $this->drupalCreateUser(array('access secured pages'));
    //todo curl options
    $this->curl_options[CURLOPT_HTTPAUTH] = CURLAUTH_DIGEST;
  }

  /**
   * Request home page with wrong password.
   */
  function testSecureSiteTypeDigestUserStoredWrong() {
    $this->curl_options[CURLOPT_USERPWD] = $this->user->name . ':' . $this->user->pass;
    $this->drupalHead(NULL);
    $this->assertResponse(401, t('Requesting home page with wrong password.'));
  }

  /**
   * Request home page with correct password.
   */
  function testSecureSiteTypeDigestUserStoredCorrect() {
    $this->curl_options[CURLOPT_USERPWD] = $this->user->name . ':' . $this->user->pass_raw;
    $this->drupalHead(NULL);
    $this->assertResponse(200, t('Requesting home page with correct password.'));
    module_load_include('inc', 'securesite');
    $directives = _securesite_parse_directives($this->drupalGetHeader('Authentication-Info'));
    $this->assertTrue(isset($directives['rspauth']), t('Checking correct password authentication info.'));
    $this->drupalHead('user/logout');
    $this->assertResponse(401, t('Requesting log-out page'));
  }

  /**
   * Implements tearDown().
   */
  function tearDown() {
    user_cancel(array(), $this->user->uid, $method = 'user_cancel_delete');
    parent::tearDown();
  }
}
