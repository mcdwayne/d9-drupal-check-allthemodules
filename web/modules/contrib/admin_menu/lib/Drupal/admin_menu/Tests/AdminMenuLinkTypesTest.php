<?php

namespace Drupal\admin_menu\Tests;

/**
 * Tests appearance of different types of links.
 */
class AdminMenuLinkTypesTest extends AdminMenuTestBase {

  public static $modules = ['help'];

  public static function getInfo() {
    return [
      'name' => 'Link types',
      'description' => 'Tests appearance of different types of links.',
      'group' => 'Administration menu',
    ];
  }

  function setUp() {
    parent::setUp();

    $this->drupalLogin($this->root_user);
  }

  /**
   * Tests appearance of different router item link types.
   */
  function testLinkTypes() {
    // Verify that MENU_NORMAL_ITEMs appear.
    $this->assertLinkTrailByTitle([
      t('Configuration'),
      t('System'),
      t('Site information'),
    ]);

    // Verify that MENU_LOCAL_TASKs appear.
    $this->assertLinkTrailByTitle([t('People'), t('Permissions')]);
    $this->assertLinkTrailByTitle([t('Appearance'), t('Settings')]);
    $this->assertLinkTrailByTitle([t('Extend'), t('Uninstall')]);

    // Verify that MENU_LOCAL_ACTIONs appear.
    $this->assertLinkTrailByTitle([
      t('People'),
      t('Add user'),
    ]);

    // Verify that MENU_DEFAULT_LOCAL_TASKs do NOT appear.
    $this->assertNoLinkTrailByTitle([t('Extend'), t('List')]);
    $this->assertNoLinkTrailByTitle([t('People'), t('List')]);
    $this->assertNoLinkTrailByTitle([t('People'), t('Permissions'), t('Permissions')]);
    $this->assertNoLinkTrailByTitle([t('Appearance'), t('List')]);

    // Verify that MENU_VISIBLE_IN_BREADCRUMB items (exact type) do NOT appear.
    $this->assertNoLinkTrailByTitle([t('Extend'), t('Uninstall'), t('Uninstall')]);
    $this->assertNoLinkTrailByTitle([t('Help'), 'admin_menu']);

    // Verify that special "Index" link appears below icon.
    $this->assertElementByXPath('//div[@id="admin-menu"]//a[contains(@href, :path) and text()=:title]', [
      ':path' => 'admin/index',
      ':title' => t('Index'),
    ], "Icon » Index link found.");
  }
}

