<?php

/**
 * @file
 * Contains Drupal\securesite\Tests\BasicAuth\SecureSiteBasicGuestSetTest
 */
namespace Drupal\securesite\Tests\BasicAuth;

use Drupal\simpletest\WebTestBase;



/**
 * Functional tests for basic authentication with guest credentials set.
 */
class SecureSiteBasicGuestSetTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('securesite');

  //todo phpdoc comments
  protected $name;

  protected $pass;


  /**
   * Implements getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => t('Basic authentication: Guest credentials set'),
      'description' => t('Test HTTP basic authentication with guest credentials set.'),
      'group' => t('Secure Site'),
    );
  }

  /**
   * Implements setUp().
   */
  function setUp() {
    parent::setUp();
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, array('access secured pages'));
    $this->name = $this->randomName();
    $this->pass = user_password();
    $config = \Drupal::config('securesite.settings');
    $config->set('securesite_guest_name', $this->name);
    $config->set('securesite_guest_pass', $this->pass);
    // Should work with all authentication methods enabled.
    $config->set('securesite_type', array(SECURESITE_FORM, SECURESITE_BASIC, SECURESITE_DIGEST));
    $config->save();
  }

  /**
   * Request home page with empty credentials.
   */
  function testSecureSiteTypeBasicGuestSetEmpty() {
    //todo curl options
    $this->curl_options[CURLOPT_USERPWD] = ':';
    $this->drupalHead(NULL);
    $this->assertResponse(403, t('Requesting home page with empty credentials.'));
    $this->drupalHead(NULL);
    $this->assertResponse(401, t('Trying to clear credentials by repeating request.'));
  }

  /**
   * Request home page with random credentials.
   */
  function testSecureSiteTypeBasicGuestSetWrong() {
    $this->curl_options[CURLOPT_USERPWD] = $this->randomName() . ':' . user_password();
    $this->drupalHead(NULL);
    $this->assertResponse(401, t('Requesting home page with random credentials.'));
  }

  /**
   * Request home page with guest credentials.
   */
  function testSecureSiteTypeBasicGuestSetCorrect() {
    $this->curl_options[CURLOPT_USERPWD] = $this->name . ':' . $this->pass;
    $this->drupalHead(NULL);
    $this->assertResponse(200, t('Requesting home page with guest credentials.'));
  }

  /**
   * Implements tearDown().
   */
  function tearDown() {
    $this->curl_options = array();
    parent::tearDown();
  }
}