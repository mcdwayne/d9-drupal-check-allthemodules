<?php

namespace Drupal\leadboxer\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Test basic functionality of LeadBoxer module.
 *
 * @group LeadBoxer
 */
class LeadBoxerBasicTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['leadboxer'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer leadboxer',
      'administer modules',
      'administer site configuration',
    ];

    // User to set up leadboxer.
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests if configuration is possible.
   */
  public function testLeadBoxerConfiguration() {
    // Check if Configure link is available on 'Extend' page.
    // Requires 'administer modules' permission.
    $this->drupalGet('admin/modules');
    $this->assertRaw('admin/config/system/leadboxer', '[testLeadBoxerConfiguration]: Configure link from Extend page to LeadBoxer settings page exists.');

    // Check if Configure link is available on 'Status Reports' page.
    // Requires 'administer site configuration' permission.
    $this->drupalGet('admin/reports/status');
    $this->assertRaw('admin/config/system/leadboxer', '[testLeadBoxerConfiguration]: Configure link from Status Reports page to LeadBoxers settings page exists.');

    // Check for setting page's presence.
    $this->drupalGet('admin/config/system/leadboxer');
    $this->assertRaw(t('LeadBoxer dataset ID'), '[testLeadBoxerConfiguration]: Settings page displayed.');
  }

  /**
   * Tests if page visibility works.
   */
  public function testLeadBoxerPageVisibility() {
    $this->drupalGet('');
    $this->assertNoRaw('//script.leadboxer.com/?account=', '[testLeadBoxerPageVisibility]: Tracking code is not displayed without a LeadBoxer dataset ID configured.');

    $leadboxer_code = '12345abc';
    $this->config('leadboxer.settings')->set('dataset_id', $leadboxer_code)->save();

    // Show tracking on "every page except the listed pages".
    $this->config('leadboxer.settings')->set('visibility.request_path_mode', 0)->save();
    // Disable tracking on "admin*" pages only.
    $this->config('leadboxer.settings')->set('visibility.request_path_pages', "/admin\n/admin/*")->save();
    // Enable tracking only for authenticated users only.
    $this->config('leadboxer.settings')->set('visibility.user_role_roles', [AccountInterface::AUTHENTICATED_ROLE => AccountInterface::AUTHENTICATED_ROLE])->save();

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertRaw($leadboxer_code, '[testLeadBoxerPageVisibility]: Tracking code is displayed for authenticated users.');

    // Test whether tracking code is not included on pages to omit.
    $this->drupalGet('admin');
    $this->assertNoRaw($leadboxer_code, '[testLeadBoxerPageVisibility]: Tracking code is not displayed on admin page.');
    $this->drupalGet('admin/config/system/leadboxer');
    // Checking for tracking URI, as $leadboxer_code is displayed in the form.
    $this->assertNoRaw('//script.leadboxer.com/?account=', '[testLeadBoxerPageVisibility]: Tracking code is not displayed on admin subpage.');

    // Test whether tracking code display is properly flipped.
    $this->config('leadboxer.settings')->set('visibility.request_path_mode', 1)->save();
    $this->drupalGet('admin');
    $this->assertRaw($leadboxer_code, '[testLeadBoxerPageVisibility]: Tracking code is displayed on admin page.');
    $this->drupalGet('admin/config/system/leadboxer');
    // Checking for tracking URI, as $leadboxer_code is displayed in the form.
    $this->assertRaw('//script.leadboxer.com/?account=', '[testLeadBoxerPageVisibility]: Tracking code is displayed on admin subpage.');
    $this->drupalGet('');
    $this->assertNoRaw($leadboxer_code, '[testLeadBoxerPageVisibility]: Tracking code is NOT displayed on front page.');

    // Test whether tracking code is not display for anonymous.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoRaw($leadboxer_code, '[testLeadBoxerPageVisibility]: Tracking code is NOT displayed for anonymous.');

    // Switch back to every page except the listed pages.
    $this->config('leadboxer.settings')->set('visibility.request_path_mode', 0)->save();
    // Enable tracking code for all user roles.
    $this->config('leadboxer.settings')->set('visibility.user_role_roles', [])->save();
  }

}
