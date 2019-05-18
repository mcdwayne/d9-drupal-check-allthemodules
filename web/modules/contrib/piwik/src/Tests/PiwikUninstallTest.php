<?php

namespace Drupal\piwik\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test uninstall functionality of Piwik module.
 *
 * @group Piwik
 */
class PiwikUninstallTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['piwik'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer piwik',
      'administer modules',
    ];

    // User to set up piwik.
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests if the module cleans up the disk on uninstall.
   */
  public function testPiwikUninstall() {
    $cache_path = 'public://piwik';
    $site_id = '1';
    $this->config('piwik.settings')->set('site_id', $site_id)->save();
    $this->config('piwik.settings')->set('url_http', 'http://www.example.com/piwik/')->save();
    $this->config('piwik.settings')->set('url_https', 'https://www.example.com/piwik/')->save();

    // Enable local caching of piwik.js.
    $this->config('piwik.settings')->set('cache', 1)->save();

    // Load front page to get the piwik.js downloaded into local cache. But
    // loading the piwik.js is not possible as "url_http" is a test dummy only.
    // Create a dummy file to complete the rest of the tests.
    file_prepare_directory($cache_path, FILE_CREATE_DIRECTORY);
    $data = $this->randomMachineName(128);
    $file_destination = $cache_path . '/piwik.js';
    file_unmanaged_save_data($data, $file_destination);
    file_unmanaged_save_data(gzencode($data, 9, FORCE_GZIP), $file_destination . '.gz', FILE_EXISTS_REPLACE);

    // Test if the directory and piwik.js exists.
    $this->assertTrue(file_prepare_directory($cache_path), 'Cache directory "public://piwik" has been found.');
    $this->assertTrue(file_exists($cache_path . '/piwik.js'), 'Cached piwik.js tracking file has been found.');
    $this->assertTrue(file_exists($cache_path . '/piwik.js.gz'), 'Cached piwik.js.gz tracking file has been found.');

    // Uninstall the module.
    $edit = [];
    $edit['uninstall[piwik]'] = TRUE;
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->assertNoText(\Drupal::translation()->translate('Configuration deletions'), 'No configuration deletions listed on the module install confirmation page.');
    $this->drupalPostForm(NULL, NULL, t('Uninstall'));
    $this->assertText(t('The selected modules have been uninstalled.'), 'Modules status has been updated.');

    // Test if the directory and all files have been removed.
    $this->assertFalse(file_scan_directory($cache_path, '/.*/'), 'Cached JavaScript files have been removed.');
    $this->assertFalse(file_prepare_directory($cache_path), 'Cache directory "public://piwik" has been removed.');
  }

}
