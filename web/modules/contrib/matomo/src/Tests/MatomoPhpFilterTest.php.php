<?php

namespace Drupal\matomo\Tests;

use Drupal\Component\Utility\Html;
use Drupal\simpletest\WebTestBase;

/**
 * Test php filter functionality of Matomo module.
 *
 * @group Matomo
 */
class MatomoPhpFilterTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['matomo', 'php'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Administrator with all permissions.
    $permissions_admin_user = [
      'access administration pages',
      'administer matomo',
      'use php for matomo tracking visibility',
    ];
    $this->admin_user = $this->drupalCreateUser($permissions_admin_user);

    // Administrator who cannot configure tracking visibility with PHP.
    $permissions_delegated_admin_user = [
      'access administration pages',
      'administer matomo',
    ];
    $this->delegated_admin_user = $this->drupalCreateUser($permissions_delegated_admin_user);
  }

  /**
   * Tests if PHP module integration works.
   */
  public function testMatomoPhpFilter() {
    $site_id = '1';
    $this->drupalLogin($this->admin_user);

    $edit = [];
    $edit['matomo_site_id'] = $site_id;
    $edit['matomo_url_http'] = 'http://www.example.com/matomo/';
    $edit['matomo_url_https'] = 'https://www.example.com/matomo/';
    // Skip url check errors in automated tests.
    $edit['matomo_url_skiperror'] = TRUE;
    $edit['matomo_visibility_request_path_mode'] = 2;
    $edit['matomo_visibility_request_path_pages'] = '<?php return 0; ?>';
    $this->drupalPostForm('admin/config/system/matomo', $edit, t('Save configuration'));

    // Compare saved setting with posted setting.
    $matomo_visibility_request_path_pages = \Drupal::config('matomo.settings')->get('visibility.request_path_pages');
    $this->assertEqual('<?php return 0; ?>', $matomo_visibility_request_path_pages, '[testMatomoPhpFilter]: PHP code snippet is intact.');

    // Check tracking code visibility.
    $this->config('matomo.settings')->set('visibility.request_path_pages', '<?php return TRUE; ?>')->save();
    $this->drupalGet('');
    $this->assertRaw('u+"matomo.php"', '[testMatomoPhpFilter]: Tracking is displayed on frontpage page.');
    $this->drupalGet('admin');
    $this->assertRaw('u+"matomo.php"', '[testMatomoPhpFilter]: Tracking is displayed on admin page.');

    $this->config('matomo.settings')->set('visibility.request_path_pages', '<?php return FALSE; ?>')->save();
    $this->drupalGet('');
    $this->assertNoRaw('u+"matomo.php"', '[testMatomoPhpFilter]: Tracking is not displayed on frontpage page.');

    // Test administration form.
    $this->config('matomo.settings')->set('visibility.request_path_pages', '<?php return TRUE; ?>')->save();
    $this->drupalGet('admin/config/system/matomo');
    $this->assertRaw(t('Pages on which this PHP code returns <code>TRUE</code> (experts only)'), '[testMatomoPhpFilter]: Permission to administer PHP for tracking visibility.');
    $this->assertRaw(Html::escape('<?php return TRUE; ?>'), '[testMatomoPhpFilter]: PHP code snippted is displayed.');

    // Login the delegated user and check if fields are visible.
    $this->drupalLogin($this->delegated_admin_user);
    $this->drupalGet('admin/config/system/matomo');
    $this->assertNoRaw(t('Pages on which this PHP code returns <code>TRUE</code> (experts only)'), '[testMatomoPhpFilter]: No permission to administer PHP for tracking visibility.');
    $this->assertRaw(Html::escape('<?php return TRUE; ?>'), '[testMatomoPhpFilter]: No permission to view PHP code snippted.');

    // Set a different value and verify that this is still the same after the
    // post.
    $this->config('matomo.settings')->set('visibility.request_path_pages', '<?php return 0; ?>')->save();

    $edit = [];
    $edit['matomo_site_id'] = $site_id;
    $edit['matomo_url_http'] = 'http://www.example.com/matomo/';
    $edit['matomo_url_https'] = 'https://www.example.com/matomo/';
    // Required for testing only.
    $edit['matomo_url_skiperror'] = TRUE;
    $this->drupalPostForm('admin/config/system/matomo', $edit, t('Save configuration'));

    // Compare saved setting with posted setting.
    $matomo_visibility_request_path_mode = $this->config('matomo.settings')->get('visibility.request_path_mode');
    $matomo_visibility_request_path_pages = $this->config('matomo.settings')->get('visibility.request_path_pages');
    $this->assertEqual(2, $matomo_visibility_request_path_mode, '[testMatomoPhpFilter]: Pages on which this PHP code returns TRUE is selected.');
    $this->assertEqual('<?php return 0; ?>', $matomo_visibility_request_path_pages, '[testMatomoPhpFilter]: PHP code snippet is intact.');
  }

}
