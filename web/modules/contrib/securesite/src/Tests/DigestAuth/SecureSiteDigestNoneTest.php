<?php

/**
 * @file
 * Contains Drupal\securesite\Tests\DigestAuth\SecureSiteDigestNoneTest
 */
namespace Drupal\securesite\Tests\DigestAuth;

use Drupal\simpletest\WebTestBase;
/**
 * Functional test for digest authentication without credentials.
 */
class SecureSiteDigestNoneTest extends WebTestBase {

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
      'name' => t('Digest authentication: No credentials'),
      'description' => t('Test HTTP digest authentication without credentials. Digest scripts must be configured on the live site before this test is run.'),
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
  }

  /**
   * Request home page without credentials.
   */
  function testSecureSiteTypeDigestNone() {
    $this->drupalHead(NULL);
    $this->assertResponse(401, t('Requesting home page without credentials.'));
    $challenge = array();
    list($scheme, $value) = explode(' ', $this->drupalGetHeader('WWW-Authenticate'), 2);
    if ($scheme == 'Digest') {
      module_load_include('inc', 'securesite');
      $challenge = _securesite_parse_directives($value);
    }
    $this->assertTrue(isset($challenge['realm']) && isset($challenge['nonce']), t('Checking for digest authentication scheme.'));
  }
}
