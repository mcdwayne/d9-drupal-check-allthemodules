<?php

namespace Drupal\matomo\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Test roles functionality of Matomo module.
 *
 * @group Matomo
 */
class MatomoRolesTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['matomo'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer matomo',
    ];

    // User to set up matomo.
    $this->admin_user = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests if roles based tracking works.
   */
  public function testMatomoRolesTracking() {
    $site_id = '1';
    $this->config('matomo.settings')->set('site_id', $site_id)->save();
    $this->config('matomo.settings')->set('url_http', 'http://www.example.com/matomo/')->save();
    $this->config('matomo.settings')->set('url_https', 'https://www.example.com/matomo/')->save();

    // Test if the default settings are working as expected.
    // Add to the selected roles only.
    $this->config('matomo.settings')->set('visibility.user_role_mode', 0)->save();
    // Enable tracking for all users.
    $this->config('matomo.settings')->set('visibility.user_role_roles', [])->save();

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertRaw('u+"matomo.php"', '[testMatomoRoleVisibility]: Tracking code is displayed for anonymous users on frontpage with default settings.');
    $this->drupalGet('admin');
    $this->assertRaw('"403/URL = "', '[testMatomoRoleVisibility]: 403 Forbidden tracking code is displayed for anonymous users in admin section with default settings.');

    $this->drupalLogin($this->admin_user);

    $this->drupalGet('');
    $this->assertRaw('u+"matomo.php"', '[testMatomoRoleVisibility]: Tracking code is displayed for authenticated users on frontpage with default settings.');
    $this->drupalGet('admin');
    $this->assertNoRaw('u+"matomo.php"', '[testMatomoRoleVisibility]: Tracking code is NOT displayed for authenticated users in admin section with default settings.');

    // Test if the non-default settings are working as expected.
    // Enable tracking only for authenticated users.
    $this->config('matomo.settings')->set('visibility.user_role_roles', [AccountInterface::AUTHENTICATED_ROLE => AccountInterface::AUTHENTICATED_ROLE])->save();

    $this->drupalGet('');
    $this->assertRaw('u+"matomo.php"', '[testMatomoRoleVisibility]: Tracking code is displayed for authenticated users only on frontpage.');

    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoRaw('u+"matomo.php"', '[testMatomoRoleVisibility]: Tracking code is NOT displayed for anonymous users on frontpage.');

    // Add to every role except the selected ones.
    $this->config('matomo.settings')->set('visibility.user_role_mode', 1)->save();
    // Enable tracking for all users.
    $this->config('matomo.settings')->set('visibility.user_role_roles', [])->save();

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertRaw('u+"matomo.php"', '[testMatomoRoleVisibility]: Tracking code is added to every role and displayed for anonymous users.');
    $this->drupalGet('admin');
    $this->assertRaw('"403/URL = "', '[testMatomoRoleVisibility]: 403 Forbidden tracking code is shown for anonymous users if every role except the selected ones is selected.');

    $this->drupalLogin($this->admin_user);

    $this->drupalGet('');
    $this->assertRaw('u+"matomo.php"', '[testMatomoRoleVisibility]: Tracking code is added to every role and displayed on frontpage for authenticated users.');
    $this->drupalGet('admin');
    $this->assertNoRaw('u+"matomo.php"', '[testMatomoRoleVisibility]: Tracking code is added to every role and NOT displayed in admin section for authenticated users.');

    // Disable tracking for authenticated users.
    $this->config('matomo.settings')->set('visibility.user_role_roles', [AccountInterface::AUTHENTICATED_ROLE => AccountInterface::AUTHENTICATED_ROLE])->save();

    $this->drupalGet('');
    $this->assertNoRaw('u+"matomo.php"', '[testMatomoRoleVisibility]: Tracking code is NOT displayed on frontpage for excluded authenticated users.');
    $this->drupalGet('admin');
    $this->assertNoRaw('u+"matomo.php"', '[testMatomoRoleVisibility]: Tracking code is NOT displayed in admin section for excluded authenticated users.');

    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertRaw('u+"matomo.php"', '[testMatomoRoleVisibility]: Tracking code is displayed on frontpage for included anonymous users.');
  }

}
