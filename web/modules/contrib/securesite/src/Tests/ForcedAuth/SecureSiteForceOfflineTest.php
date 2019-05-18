<?php
/**
 * @file
 * Contains Drupal\securesite\Tests\ForcedAuth\SecureSiteForceOfflineTest
 */
namespace Drupal\securesite\Tests\ForcedAuth;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for page requests with authentication forced when site is
 * off line.
 */
class SecureSiteForceOfflineTest extends WebTestBase {
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
      'name' => t('Forced authentication: Site off line'),
      'description' => t('Test page requests with authentication forced when site is off line.'),
      'group' => t('Secure Site'),
    );
  }

  /**
   * Implements setUp().
   */
  function setUp() {
    parent::setUp();
    \Drupal::config('securesite.settings')->set('securesite_enabled', SECURESITE_OFFLINE)->save();
  }

  /**
   * Request on-line home page.
   */
  function testSecureSiteForceOfflineNormal() {
    $this->drupalHead(NULL);
    $this->assertResponse(200, t('Requesting on-line home page.'));
  }

  /**
   * Request off-line home page.
   */
  function testSecureSiteForceOfflineMaintenance() {
    \Drupal::state()->set('system.maintenance_mode', 1);
    $this->drupalHead(NULL);
    $this->assertResponse(401, t('Requesting off-line home page.'));
  }
}