<?php
/**
 * @file
 * Contains Drupal\securesite\Tests\ForcedAuth\SecureSiteConfig403Test
 */

namespace Drupal\securesite\Tests\ForcedAuth;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for configuring access denied page.
 */
class SecureSiteConfig403Test extends WebTestBase {

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
      'name' => '403 error configuration',
      'description' => t('Test configuration for access denied page.'),
      'group' => t('Secure Site'),
    );
  }

  /**
   * Implements setUp().
   */
  function setUp() {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser(array('administer site configuration')));
  }

  /**
   * Check access denied page when setting forced authentication on restricted pages.
   */
  function testSecureSiteConfig403Save() {
    $this->drupalPostForm('admin/config/securesite', array('securesite_enabled' => SECURESITE_403), 'Save configuration');
    $this->assertTrue( \Drupal::config('system.site')->get('page.403') == 'securesite_403', t('Checking access denied page when setting forced authentication on restricted pages.'));
  }

  /**
   * Keep current access denied page when no previous setting exists.
   */
  function testSecureSiteConfig403ResetCurrent() {
    \Drupal::config('system.site')->set('page.403', 'site_403')->save();
    $this->drupalPostForm('admin/config/securesite', array(), 'Reset to defaults');
    $this->assertTrue(\Drupal::config('system.site')->get('page.403') == 'site_403', t('Keeping current access denied page when no previous setting exists.'));
  }

  /**
   * Save previous access denied page.
   */
  function testSecureSiteConfig403Page() {
    $this->drupalPostForm('admin/config/development/logging', array('site_403' => 'site_403'), 'Save configuration');
    $config = \Drupal::config('securesite.settings');
    $config->set('securesite_enabled', SECURESITE_403)->save();
    $this->drupalPostForm('admin/config/development/logging', array(), 'Save configuration');
    $this->assertTrue($config->get('securesite_403') == 'site_403', t('Saving previous access denied page.'));
  }

  /**
   * Restore previous access denied page.
   */
  function testSecureSiteConfig403ResetPrevious() {
    \Drupal::config('securesite.settings')->set('securesite_403', 'site_403')->save();
    $this->drupalPostForm('admin/config/development/logging', array(), 'Reset to defaults');
    $this->assertTrue(\Drupal::config('system.site')->get('page.403') == 'site_403', t('Restoring previous access denied page.'));
  }

  /**
   * Implements tearDown().
   */
  function tearDown() {
    $config = \Drupal::config('securesite.settings');
    $config->clear('securesite_enabled');
    $config->clear('securesite_403');
    $config->save();
    \Drupal::config('system.site')->clear('page.403')->save();
    parent::tearDown();
  }
}