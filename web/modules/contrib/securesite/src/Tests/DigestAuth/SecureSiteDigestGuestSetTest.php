<?php
/**
 * @file
 * Contains Drupal\securesite\Tests\DigestAuth\SecureSiteDigestGuestSetTest
 */
namespace Drupal\securesite\Tests\DigestAuth;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for digest authentication with guest credentials set.
 */
class SecureSiteDigestGuestSetTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('securesite');

  //todo phpdoc
  protected $user;
  protected $guest_name;
  protected $guest_pass;
  /**
   * Implements getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => t('Digest authentication: Guest credentials set'),
      'description' => t('Test HTTP digest authentication with guest credentials set. Digest scripts must be configured on the live site before these tests can be run.'),
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
    // Store guest credentials.
    $this->user = $this->drupalCreateUser(array('administer site configuration', 'access secured pages'));
    $this->curl_options[CURLOPT_USERPWD] = $this->user->name . ':' . $this->user->pass_raw;
    $this->guest_name = $this->randomName();
    $this->guest_pass = user_password();
    $this->drupalPostForm('admin/settings/securesite', array('securesite_guest_name' => $this->guest_name, 'securesite_guest_pass' => $this->guest_pass, 'securesite_type[' . SECURESITE_DIGEST . ']' => TRUE), 'Save configuration');
    $this->curlClose();
  }

  /**
   * Request home page with empty credentials.
   */
  function testSecureSiteDigestGuestSetEmpty() {
    $this->curl_options[CURLOPT_USERPWD] = ':';
    $this->drupalHead(NULL);
    $this->assertResponse(403, t('Requesting home page with empty credentials.'));
  }

  /**
   * Request home page with random credentials.
   */
  function testSecureSiteDigestGuestSetRandom() {
    $this->curl_options[CURLOPT_USERPWD] = $this->randomName() . ':' . user_password();
    $this->drupalHead(NULL);
    $this->assertResponse(401, t('Requesting home page with random credentials.'));
  }

  /**
   * Request home page with guest credentials.
   */
  function testSecureSiteDigestGuestSetCorrect() {
    $this->curl_options[CURLOPT_USERPWD] = $this->guest_name . ':' . $this->guest_pass;
    $this->drupalGet(NULL);
    $this->assertResponse(200, t('Requesting home page with guest credentials.'));
    module_load_include('inc', 'securesite');
    $directives = _securesite_parse_directives($this->drupalGetHeader('Authentication-Info'));
    $this->assertTrue(isset($directives['rspauth']), t('Checking guest credentials authentication info.'));
  }

  /**
   * Implements tearDown().
   */
  function tearDown() {
    $this->curl_options[CURLOPT_USERPWD] = $this->user->name . ':' . $this->user->pass_raw;
    $this->drupalPost('admin/config/securesite', array(), 'Reset to defaults');
    _securesite_copy_script_config($this);
    \Drupal::config('securesite.settings')->set('securesite_type', array(SECURESITE_DIGEST))->save();
    user_cancel(array(), $this->user->uid, $method = 'user_cancel_delete');
    parent::tearDown();
  }
}
