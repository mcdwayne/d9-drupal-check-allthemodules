<?php

namespace Drupal\piwik\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Test roles functionality of Piwik module.
 *
 * @group Piwik
 */
class PiwikRolesTest extends WebTestBase {

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
    ];

    // User to set up piwik.
    $this->admin_user = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests if roles based tracking works.
   */
  public function testPiwikRolesTracking() {
    $site_id = '1';
    $this->config('piwik.settings')->set('site_id', $site_id)->save();
    $this->config('piwik.settings')->set('url_http', 'http://www.example.com/piwik/')->save();
    $this->config('piwik.settings')->set('url_https', 'https://www.example.com/piwik/')->save();

    // Test if the default settings are working as expected.
    // Add to the selected roles only.
    $this->config('piwik.settings')->set('visibility.user_role_mode', 0)->save();
    // Enable tracking for all users.
    $this->config('piwik.settings')->set('visibility.user_role_roles', [])->save();

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertRaw('u+"piwik.php"', '[testPiwikRoleVisibility]: Tracking code is displayed for anonymous users on frontpage with default settings.');
    $this->drupalGet('admin');
    $this->assertRaw('"403/URL = "', '[testPiwikRoleVisibility]: 403 Forbidden tracking code is displayed for anonymous users in admin section with default settings.');

    $this->drupalLogin($this->admin_user);

    $this->drupalGet('');
    $this->assertRaw('u+"piwik.php"', '[testPiwikRoleVisibility]: Tracking code is displayed for authenticated users on frontpage with default settings.');
    $this->drupalGet('admin');
    $this->assertNoRaw('u+"piwik.php"', '[testPiwikRoleVisibility]: Tracking code is NOT displayed for authenticated users in admin section with default settings.');

    // Test if the non-default settings are working as expected.
    // Enable tracking only for authenticated users.
    $this->config('piwik.settings')->set('visibility.user_role_roles', [AccountInterface::AUTHENTICATED_ROLE => AccountInterface::AUTHENTICATED_ROLE])->save();

    $this->drupalGet('');
    $this->assertRaw('u+"piwik.php"', '[testPiwikRoleVisibility]: Tracking code is displayed for authenticated users only on frontpage.');

    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoRaw('u+"piwik.php"', '[testPiwikRoleVisibility]: Tracking code is NOT displayed for anonymous users on frontpage.');

    // Add to every role except the selected ones.
    $this->config('piwik.settings')->set('visibility.user_role_mode', 1)->save();
    // Enable tracking for all users.
    $this->config('piwik.settings')->set('visibility.user_role_roles', [])->save();

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertRaw('u+"piwik.php"', '[testPiwikRoleVisibility]: Tracking code is added to every role and displayed for anonymous users.');
    $this->drupalGet('admin');
    $this->assertRaw('"403/URL = "', '[testPiwikRoleVisibility]: 403 Forbidden tracking code is shown for anonymous users if every role except the selected ones is selected.');

    $this->drupalLogin($this->admin_user);

    $this->drupalGet('');
    $this->assertRaw('u+"piwik.php"', '[testPiwikRoleVisibility]: Tracking code is added to every role and displayed on frontpage for authenticated users.');
    $this->drupalGet('admin');
    $this->assertNoRaw('u+"piwik.php"', '[testPiwikRoleVisibility]: Tracking code is added to every role and NOT displayed in admin section for authenticated users.');

    // Disable tracking for authenticated users.
    $this->config('piwik.settings')->set('visibility.user_role_roles', [AccountInterface::AUTHENTICATED_ROLE => AccountInterface::AUTHENTICATED_ROLE])->save();

    $this->drupalGet('');
    $this->assertNoRaw('u+"piwik.php"', '[testPiwikRoleVisibility]: Tracking code is NOT displayed on frontpage for excluded authenticated users.');
    $this->drupalGet('admin');
    $this->assertNoRaw('u+"piwik.php"', '[testPiwikRoleVisibility]: Tracking code is NOT displayed in admin section for excluded authenticated users.');

    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertRaw('u+"piwik.php"', '[testPiwikRoleVisibility]: Tracking code is displayed on frontpage for included anonymous users.');
  }

}
