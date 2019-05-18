<?php

namespace Drupal\piwik\Tests;

use Drupal\Component\Utility\Html;
use Drupal\simpletest\WebTestBase;

/**
 * Test php filter functionality of Piwik module.
 *
 * @group Piwik
 */
class PiwikPhpFilterTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['piwik', 'php'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Administrator with all permissions.
    $permissions_admin_user = [
      'access administration pages',
      'administer piwik',
      'use PHP for piwik tracking visibility',
    ];
    $this->admin_user = $this->drupalCreateUser($permissions_admin_user);

    // Administrator who cannot configure tracking visibility with PHP.
    $permissions_delegated_admin_user = [
      'access administration pages',
      'administer piwik',
    ];
    $this->delegated_admin_user = $this->drupalCreateUser($permissions_delegated_admin_user);
  }

  /**
   * Tests if PHP module integration works.
   */
  public function testPiwikPhpFilter() {
    $site_id = '1';
    $this->drupalLogin($this->admin_user);

    $edit = [];
    $edit['piwik_site_id'] = $site_id;
    $edit['piwik_url_http'] = 'http://www.example.com/piwik/';
    $edit['piwik_url_https'] = 'https://www.example.com/piwik/';
    // Skip url check errors in automated tests.
    $edit['piwik_url_skiperror'] = TRUE;
    $edit['piwik_visibility_request_path_mode'] = 2;
    $edit['piwik_visibility_request_path_pages'] = '<?php return 0; ?>';
    $this->drupalPostForm('admin/config/system/piwik', $edit, t('Save configuration'));

    // Compare saved setting with posted setting.
    $piwik_visibility_request_path_pages = \Drupal::config('piwik.settings')->get('visibility.request_path_pages');
    $this->assertEqual('<?php return 0; ?>', $piwik_visibility_request_path_pages, '[testPiwikPhpFilter]: PHP code snippet is intact.');

    // Check tracking code visibility.
    $this->config('piwik.settings')->set('visibility.request_path_pages', '<?php return TRUE; ?>')->save();
    $this->drupalGet('');
    $this->assertRaw('u+"piwik.php"', '[testPiwikPhpFilter]: Tracking is displayed on frontpage page.');
    $this->drupalGet('admin');
    $this->assertRaw('u+"piwik.php"', '[testPiwikPhpFilter]: Tracking is displayed on admin page.');

    $this->config('piwik.settings')->set('visibility.request_path_pages', '<?php return FALSE; ?>')->save();
    $this->drupalGet('');
    $this->assertNoRaw('u+"piwik.php"', '[testPiwikPhpFilter]: Tracking is not displayed on frontpage page.');

    // Test administration form.
    $this->config('piwik.settings')->set('visibility.request_path_pages', '<?php return TRUE; ?>')->save();
    $this->drupalGet('admin/config/system/piwik');
    $this->assertRaw(t('Pages on which this PHP code returns <code>TRUE</code> (experts only)'), '[testPiwikPhpFilter]: Permission to administer PHP for tracking visibility.');
    $this->assertRaw(Html::escape('<?php return TRUE; ?>'), '[testPiwikPhpFilter]: PHP code snippted is displayed.');

    // Login the delegated user and check if fields are visible.
    $this->drupalLogin($this->delegated_admin_user);
    $this->drupalGet('admin/config/system/piwik');
    $this->assertNoRaw(t('Pages on which this PHP code returns <code>TRUE</code> (experts only)'), '[testPiwikPhpFilter]: No permission to administer PHP for tracking visibility.');
    $this->assertRaw(Html::escape('<?php return TRUE; ?>'), '[testPiwikPhpFilter]: No permission to view PHP code snippted.');

    // Set a different value and verify that this is still the same after the
    // post.
    $this->config('piwik.settings')->set('visibility.request_path_pages', '<?php return 0; ?>')->save();

    $edit = [];
    $edit['piwik_site_id'] = $site_id;
    $edit['piwik_url_http'] = 'http://www.example.com/piwik/';
    $edit['piwik_url_https'] = 'https://www.example.com/piwik/';
    // Required for testing only.
    $edit['piwik_url_skiperror'] = TRUE;
    $this->drupalPostForm('admin/config/system/piwik', $edit, t('Save configuration'));

    // Compare saved setting with posted setting.
    $piwik_visibility_request_path_mode = $this->config('piwik.settings')->get('visibility.request_path_mode');
    $piwik_visibility_request_path_pages = $this->config('piwik.settings')->get('visibility.request_path_pages');
    $this->assertEqual(2, $piwik_visibility_request_path_mode, '[testPiwikPhpFilter]: Pages on which this PHP code returns TRUE is selected.');
    $this->assertEqual('<?php return 0; ?>', $piwik_visibility_request_path_pages, '[testPiwikPhpFilter]: PHP code snippet is intact.');
  }

}
