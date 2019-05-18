<?php

namespace Drupal\admin_menu\Tests;

/**
 * Tests menu links depending on user permissions.
 */
class AdminMenuPermissionsTest extends AdminMenuTestBase {

  public static $modules = ['node'];

  public static function getInfo() {
    return [
      'name' => 'Menu link access permissions',
      'description' => 'Tests appearance of menu links depending on user permissions.',
      'group' => 'Administration menu',
    ];
  }

  /**
   * Test that the links are added to the page (no JS testing).
   */
  function testPermissions() {
    module_enable(['contact']);
    $this->resetAll();

    // Anonymous users should not see the menu.
    $this->drupalGet('');
    $this->assertNoElementByXPath('//div[@id="admin-menu"]', [], t('Administration menu not found.'));

    // Create a user who
    // - can access content overview
    // - cannot access drupal.org links
    // - cannot administer Contact module
    $permissions = $this->basePermissions + [
      'access content overview',
    ];
    $admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($admin_user);

    // Check that the user can see the admin links, but not the drupal links.
    $this->assertElementByXPath('//div[@id="admin-menu"]', [], 'Administration menu found.');
    $this->assertElementByXPath('//div[@id="admin-menu"]//a[contains(@href, :path)]', [':path' => 'admin/content'], 'Content link found.');
    $this->assertNoElementByXPath('//div[@id="admin-menu"]//a[@href=:path]', [':path' => 'http://drupal.org'], 'Icon » Drupal.org link not found.');
    $this->assertNoElementByXPath('//div[@id="admin-menu"]//a[contains(@href, :path)]', [':path' => 'admin/structure/contact'], 'Structure » Contact link not found.');

    // Create a user "reversed" to the above; i.e., who
    // - cannot access content overview
    // - can access drupal.org links
    // - can administer Contact module
    $permissions = $this->basePermissions + [
      'display drupal links',
      'administer contact forms',
    ];
    $admin_user2 = $this->drupalCreateUser($permissions);
    $this->drupalLogin($admin_user2);
    $this->assertElementByXPath('//div[@id="admin-menu"]', [], 'Administration menu found.');
    $this->assertNoElementByXPath('//div[@id="admin-menu"]//a[contains(@href, :path)]', [':path' => 'admin/content'], 'Content link not found.');
    $this->assertElementByXPath('//div[@id="admin-menu"]//a[@href=:path]', [':path' => 'http://drupal.org'], 'Icon » Drupal.org link found.');
    $this->assertElementByXPath('//div[@id="admin-menu"]//a[contains(@href, :path)]', [':path' => 'admin/structure/contact'], 'Structure » Contact link found.');
  }

  /**
   * Tests handling of links pointing to category/overview pages.
   */
  function testCategories() {
    // Create a user with minimum permissions.
    $admin_user = $this->drupalCreateUser($this->basePermissions);
    $this->drupalLogin($admin_user);

    // Verify that no category links appear.
    $this->assertNoLinkTrailByTitle([t('Structure')]);
    $this->assertNoLinkTrailByTitle([t('Configuration')]);

    // Create a user with access to one configuration category.
    $permissions = $this->basePermissions + [
      'administer users',
    ];
    $admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($admin_user);

    // Verify that only expected category links appear.
    $this->assertNoLinkTrailByTitle([t('Structure')]);
    $this->assertLinkTrailByTitle([t('People')]);
    $this->assertLinkTrailByTitle([t('Configuration')]);
    $this->assertLinkTrailByTitle([t('Configuration'), t('People')]);
    // Random picks are sufficient.
    $this->assertNoLinkTrailByTitle([t('Configuration'), t('Media')]);
    $this->assertNoLinkTrailByTitle([t('Configuration'), t('System')]);
  }

  /**
   * Tests that user role and permission changes are properly taken up.
   */
  function testPermissionChanges() {
    // Create a user who is able to change permissions.
    $permissions = $this->basePermissions + [
      'administer permissions',
    ];
    $admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($admin_user);

    // Extract the user role ID that was created for above permissions.
    $rid = key(array_diff_key($admin_user->roles, [DRUPAL_AUTHENTICATED_RID => 0]));

    // Verify that Configuration does not appear.
    $this->assertNoLinkTrailByTitle([t('Configuration')]);
    // Grant the 'administer site configuration' permission to ourselves.
    $edit = [
      $rid . '[administer site configuration]' => TRUE,
    ];
    $this->drupalPost('admin/people/permissions', $edit, t('Save permissions'));
    // Verify that Configuration appears.
    $this->assertLinkTrailByTitle([t('Configuration')]);

    // Verify that Structure » Content types does not appear.
    $this->assertNoLinkTrailByTitle([t('Structure'), t('Content types')]);
    // Create a new role.
    $test_rid = drupal_strtolower($this->randomName(8));
    $edit = [
      'role[label]' => 'test',
      'role[id]' => $test_rid,
    ];
    $this->drupalPost('admin/people/roles', $edit, t('Add role'));
    // Grant the 'administer content types' permission for the role.
    $edit = [
      $test_rid . '[administer content types]' => TRUE,
    ];
    $this->drupalPost('admin/people/permissions/' . $test_rid, $edit, t('Save permissions'));
    // Verify that Structure » Content types does not appear.
    $this->assertNoLinkTrailByTitle([t('Structure'), t('Content types')]);

    // Assign the role to ourselves.
    $edit = [
      'roles[' . $test_rid . ']' => TRUE,
    ];
    $this->drupalPost('user/' . $admin_user->uid . '/edit', $edit, t('Save'));
    // Verify that Structure » Content types appears.
    $this->assertLinkTrailByTitle([t('Structure'), t('Content types')]);

    // Delete the role.
    $this->drupalPost('admin/people/roles/edit/' . $test_rid, [], t('Delete role'));
    $this->drupalPost(NULL, [], t('Delete'));
    // Verify that Structure » Content types does not appear.
    $this->assertNoLinkTrailByTitle([t('Structure'), t('Content types')]);
  }
}

