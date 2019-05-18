<?php

namespace Drupal\admin_menu\Tests;

/**
 * Tests appearance, localization, and escaping of dynamic links.
 */
class AdminMenuDynamicLinksTest extends AdminMenuTestBase {

  public static $modules = ['node'];

  public static function getInfo() {
    return [
      'name' => 'Dynamic links',
      'description' => 'Tests appearance, localization, and escaping of dynamic links.',
      'group' => 'Administration menu',
    ];
  }

  /**
   * Tests node type links.
   */
  function testNode() {

    // The $type is used on tests. @codingStandardsIgnoreLine
    $type = $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Create a content-type with special characters.
    $type = $this->drupalCreateContentType(['type' => 'special', 'name' => 'Cool & Special']);

    $permissions = $this->basePermissions + [
      'administer content types',
      // @todo D8: node_access() unconditionally checks for the 'access content'
      //   permission for all $ops.
      'access content',
      'create article content',
      'create special content',
    ];
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);

    // Verify that dynamic links are displayed.
    $this->drupalGet('');
    $this->assertElementByXPath('//div[@id="admin-menu"]', [], t('Administration menu found.'));
    $this->assertElementByXPath('//div[@id="admin-menu"]//a[contains(@href, :path)]', [':path' => 'admin/structure/types'], "Structure » Content types link found.");

    // Verify link title output escaping.
    $this->assertNoRaw('Cool & Special');
    $this->assertRaw(check_plain('Cool & Special'));

    // Verify Manage content type links.
    $links = [
      'admin/structure/types/manage/article' => 'Article',
      'admin/structure/types/manage/special' => 'Cool & Special',
    ];
    foreach ($links as $path => $title) {
      $this->assertElementByXPath('//div[@id="admin-menu"]//a[contains(@href, :path) and text()=:title]', [
        ':path' => $path,
        ':title' => $title,
      ], "Structure » Content types » $title link found.");
    }

    // Verify Add content links.
    // @todo Fix expansion of node/add.
    return;
    $links = [
      'node/add/article' => 'Article',
      'node/add/special' => 'Cool & Special',
    ];
    foreach ($links as $path => $title) {
      $this->assertElementByXPath('//div[@id="admin-menu"]//a[contains(@href, :path) and text()=:title]', [
        ':path' => $path,
        ':title' => $title,
      ], "Add content » $title link found.");
    }
  }

  /**
   * Tests Add content links.
   */
  function testNodeAdd() {

    // The $type is used on tests. @codingStandardsIgnoreLine
    $type = $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Verify that "Add content" does not appear for unprivileged users.
    $permissions = $this->basePermissions + [
      'access content',
    ];
    $this->web_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->web_user);
    $this->assertNoText(t('Add content'));

    // Verify "Add content" appears below "Content" for administrative users.
    $permissions = $this->basePermissions + [
      'access content overview',
      'access content',
      'create article content',
    ];
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
    $this->assertLinkTrailByTitle([
      t('Content'),
      t('Add content'),
    ]);

    // Verify "Add content" appears on the top-level for regular users.
    $permissions = $this->basePermissions + [
      'access content',
      'create article content',
    ];
    $this->web_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->web_user);
    $this->assertLinkTrailByTitle([
      t('Add content'),
    ]);
  }
}

