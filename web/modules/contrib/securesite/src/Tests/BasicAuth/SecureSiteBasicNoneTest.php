<?php
/**
 * @file
 * Contains Drupal\securesite\Tests\BasicAuth\SecureSiteBasicNoneTest
 */
namespace Drupal\securesite\Tests\BasicAuth;

use Drupal\simpletest\WebTestBase;

/**
 * Functional test for basic authentication without credentials.
 */
class SecureSiteBasicNoneTest extends WebTestBase {

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
      'name' => t('Basic authentication: No credentials'),
      'description' => t('Test HTTP basic authentication without credentials.'),
      'group' => t('Secure Site'),
    );
  }

  /**
   * Implements setUp().
   */
  function setUp() {
    parent::setUp();
    \Drupal::config('securesite.settings')->set('securesite_enabled', SECURESITE_ALWAYS);
  }

  /**
   * Request home page without credentials.
   */
  function testSecureSiteTypeBasicNone() {
    $this->drupalHead(NULL);
    $this->assertResponse(401, t('Requesting home page.'));
    $found_scheme = FALSE;
    if (stripos($this->drupalGetHeader('WWW-Authenticate'), 'Basic') === 0) {
      $found_scheme = TRUE;
    }
    $this->assertTrue($found_scheme, t('Checking for basic authentication scheme.'));
  }
}
