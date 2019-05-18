<?php

/**
 * @file
 * Contains Drupal\securesite\Tests\DigestAuth\SecureSiteDigestUserUnstoredTest
 */
namespace Drupal\securesite\Tests\DigestAuth;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for digest authentication with user credentials.
 */
class SecureSiteDigestUserUnstoredTest extends WebTestBase {

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
      'name' => t('Digest authentication: User credentials unstored'),
      'description' => t('Test HTTP digest authentication with unstored user credentials. Digest scripts must be configured on the live site before these tests can be run.'),
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
    $this->user = $this->drupalCreateUser(array('access secured pages'));
    $config = \Drupal::config('securesite.settings');
    $config->set('securesite_enabled', SECURESITE_ALWAYS);
    // Should work with all authentication methods enabled.
    $config->set('securesite_type', array(SECURESITE_FORM, SECURESITE_BASIC, SECURESITE_DIGEST));
    $config->save();
    //todo curl options
    $this->curl_options[CURLOPT_HTTPAUTH] = CURLAUTH_DIGEST;
    $this->curl_options[CURLOPT_USERPWD] = $this->user->name . ':' . $this->user->pass_raw;
  }

  /**
   * Request home page with basic fall-back.
   */
  function testSecureSiteTypeDigestUserUnstoredBasic() {
    $this->drupalHead(NULL);
    $this->assertResponse(401, t('Requesting home page with basic fall-back.'));
    $found_scheme = FALSE;
    if (stripos($this->drupalGetHeader('WWW-Authenticate'), 'Basic') === 0) {
      $found_scheme = TRUE;
    }
    $this->assertTrue($found_scheme, t('Checking for basic authentication fall-back.'));
  }

  /**
   * Request home page with form fall-back.
   */
  function testSecureSiteTypeDigestUserUnstoredForm() {
    \Drupal::config('securesite.settings')->set('securesite_type', array(SECURESITE_FORM, SECURESITE_DIGEST))->save();
    $this->drupalGet(NULL);
    $this->assertResponse(200, t('Requesting home page with form fall-back.'));
    $this->assertFieldByXPath('//form[@id="securesite-user-login"]', '', t('Checking for authentication form fall-back.'), 'Other');
  }

  /**
   * Store password with fall-back authentication method.
   */
  function testSecureSiteTypeDigestUserUnstoredStore() {
    $this->curl_options[CURLOPT_HTTPAUTH] = CURLAUTH_DIGEST | CURLAUTH_BASIC;
    $this->drupalHead(NULL);
    $this->curlClose();
    $this->drupalHead(NULL);
    $this->assertResponse(200, t('Storing password with fall-back authentication method.'));
    module_load_include('inc', 'securesite');
    $directives = _securesite_parse_directives($this->drupalGetHeader('Authentication-Info'));
    $this->assertTrue(isset($directives['rspauth']), t('Checking stored password authentication info.'));
  }

  /**
   * Implements tearDown().
   */
  function tearDown() {
    user_cancel(array(), $this->user->uid, $method = 'user_cancel_delete');
    parent::tearDown();
  }
}