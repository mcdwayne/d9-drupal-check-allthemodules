<?php

namespace Drupal\leadboxer\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Test roles functionality of LeadBoxer module.
 *
 * @group LeadBoxer
 */
class LeadBoxerRolesTest extends WebTestBase {

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
    ];

    // User to set up leadboxer.
    $this->admin_user = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests if roles based tracking works.
   */
  public function testLeadBoxerRolesTracking() {
    $leadboxer_code = '12345abc';
    $this->config('leadboxer.settings')->set('dataset_id', $leadboxer_code)->save();

    // Test if the default settings are working as expected.
    // Add to the selected roles only.
    $this->config('leadboxer.settings')->set('visibility.user_role_mode', 0)->save();
    // Enable tracking for all users.
    $this->config('leadboxer.settings')->set('visibility.user_role_roles', [])->save();

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertRaw($leadboxer_code, '[testLeadBoxerRoleVisibility]: Tracking code is displayed for anonymous users on frontpage with default settings.');

    $this->drupalLogin($this->admin_user);

    $this->drupalGet('');
    $this->assertRaw($leadboxer_code, '[testLeadBoxerRoleVisibility]: Tracking code is displayed for authenticated users on frontpage with default settings.');
    $this->drupalGet('admin');
    $this->assertNoRaw($leadboxer_code, '[testLeadBoxerRoleVisibility]: Tracking code is NOT displayed for authenticated users in admin section with default settings.');

    // Test if the non-default settings are working as expected.
    // Enable tracking only for authenticated users.
    $this->config('leadboxer.settings')->set('visibility.user_role_roles', [AccountInterface::AUTHENTICATED_ROLE => AccountInterface::AUTHENTICATED_ROLE])->save();

    $this->drupalGet('');
    $this->assertRaw($leadboxer_code, '[testLeadBoxerRoleVisibility]: Tracking code is displayed for authenticated users only on frontpage.');

    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoRaw($leadboxer_code, '[testLeadBoxerRoleVisibility]: Tracking code is NOT displayed for anonymous users on frontpage.');

    // Add to every role except the selected ones.
    $this->config('leadboxer.settings')->set('visibility.user_role_mode', 1)->save();
    // Enable tracking for all users.
    $this->config('leadboxer.settings')->set('visibility.user_role_roles', [])->save();

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertRaw($leadboxer_code, '[testLeadBoxerRoleVisibility]: Tracking code is added to every role and displayed for anonymous users.');

    $this->drupalLogin($this->admin_user);

    $this->drupalGet('');
    $this->assertRaw($leadboxer_code, '[testLeadBoxerRoleVisibility]: Tracking code is added to every role and displayed on frontpage for authenticated users.');
    $this->drupalGet('admin');
    $this->assertNoRaw($leadboxer_code, '[testLeadBoxerRoleVisibility]: Tracking code is added to every role and NOT displayed in admin section for authenticated users.');

    // Disable tracking for authenticated users.
    $this->config('leadboxer.settings')->set('visibility.user_role_roles', [AccountInterface::AUTHENTICATED_ROLE => AccountInterface::AUTHENTICATED_ROLE])->save();

    $this->drupalGet('');
    $this->assertNoRaw($leadboxer_code, '[testLeadBoxerRoleVisibility]: Tracking code is NOT displayed on frontpage for excluded authenticated users.');
    $this->drupalGet('admin');
    $this->assertNoRaw($leadboxer_code, '[testLeadBoxerRoleVisibility]: Tracking code is NOT displayed in admin section for excluded authenticated users.');

    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertRaw($leadboxer_code, '[testLeadBoxerRoleVisibility]: Tracking code is displayed on frontpage for included anonymous users.');
  }

}
