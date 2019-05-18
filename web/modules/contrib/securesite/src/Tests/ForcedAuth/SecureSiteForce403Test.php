<?php
/**
 * @file
 * Contains Drupal\securesite\Tests\ForcedAuth\SecureSiteForce403Test
 */
namespace Drupal\securesite\Tests\ForcedAuth;

use Drupal\simpletest\WebTestBase;


/**
 * Functional tests for page requests with authentication forced on restricted
 * pages.
 */
class SecureSiteForce403Test extends WebTestBase {

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
      'name' => t('Forced authentication: Restricted pages'),
      'description' => t('Test page requests with authentication forced on restricted pages.'),
      'group' => t('Secure Site'),
    );
  }

  /**
   * Implements setUp().
   */
  function setUp() {
    parent::setUp('securesite');
    $config_securesite = \Drupal::config('securesite.settings');
    $config_site = \Drupal::config('system.site');
    $config_securesite->set('securesite_enabled', SECURESITE_403);
    $config_securesite->set('securesite_403', $config_site->get('page.403', ''));
    $config_securesite->save();
    $config_site->set('page.403', 'securesite_403')->save();
  }

  /**
   * Request home page.
   */
  function testSecureSiteForce403Normal() {
    $this->drupalHead(NULL);
    $this->assertResponse(200, t('Requesting home page.'));
  }

  /**
   * Request admin page.
   */
  function testSecureSiteForce403Restricted() {
    $this->drupalHead('admin');
    $this->assertResponse(401, t('Requesting admin page.'));
  }

  /**
   * Request admin page for non-admin user.
   */
  function testSecureSiteForce403User() {
    $this->drupalLogin($this->drupalCreateUser());
    $this->drupalHead('admin');
    $this->assertResponse(403, t('Requesting admin page for non-admin user.'));
  }
}
