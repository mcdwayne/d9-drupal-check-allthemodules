<?php

namespace Drupal\mmenu\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the user-facing menus in Mmenu.
 *
 * @ingroup mmenu
 *
 * @group mmenu
 */
class MmenuMenuTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('libraries', 'mmenu');

  /**
   * The installation profile to use with this test.
   *
   * This test class requires the "Tools" block.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Test for a link to the mmenu settings in the Tools menu.
   */
  public function testMmenuSettingsLink() {
    // Create administrative user.
    $admin_user = $this->drupalCreateUser(array('access administration pages', 'administer mmenu'));
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config');
    $this->assertLinkByHref('admin/config/mobile/left_mmenu_settings');
  }

  /**
   * Tests mmenu menus.
   */
  public function testMmenuSettingsMenu() {
    $admin_user = $this->drupalCreateUser(array('access administration pages', 'administer mmenu'));
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/config/mobile/left_mmenu_settings');
    $this->assertResponse(200, 'Left Menu settings page exists.');
    $this->drupalGet('admin/config/mobile/right_mmenu_settings');
    $this->assertResponse(200, 'Right Menu settings page exists.');
    $this->drupalGet('admin/config/mobile/top_mmenu_settings');
    $this->assertResponse(200, 'Top Menu settings page exists.');
    $this->drupalGet('admin/config/mobile/bottom_mmenu_settings');
    $this->assertResponse(200, 'Bottom Menu settings page exists.');
  }

}
