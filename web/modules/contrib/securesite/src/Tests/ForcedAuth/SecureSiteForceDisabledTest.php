<?php
/**
 * @file
 * Contains Drupal\securesite\Tests\ForcedAuth\SecureSiteForceDisabledTest
 */
namespace Drupal\securesite\Tests\ForcedAuth;

use Drupal\simpletest\WebTestBase;

/**
 * Functional test for page request without forced authentication.
 */
class SecureSiteForceDisabledTest extends WebTestBase {

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
      'name' => t('Forced authentication: Disabled'),
      'description' => t('Test page request without forced authentication.'),
      'group' => t('Secure Site'),
    );
  }

  /**
   * Implements setUp().
   */
  function setUp() {
    //todo find a replacement
    parent::setUp();
  }

  /**
   * Request home page without forced authentication.
   */
  function testSecureSiteForceDisabled() {
    $this->drupalHead(NULL);
    $this->assertResponse(200, t('Requesting home page.'));
  }
}