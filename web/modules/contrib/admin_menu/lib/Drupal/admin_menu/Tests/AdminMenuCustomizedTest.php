<?php

namespace Drupal\admin_menu\Tests;

/**
 * Tests customized menu links.
 */
class AdminMenuCustomizedTest extends AdminMenuTestBase {

  public static $modules = ['menu'];

  public static function getInfo() {
    return [
      'name' => 'Customized links',
      'description' => 'Tests customized menu links.',
      'group' => 'Administration menu',
    ];
  }

  function setUp() {
    parent::setUp();

    $this->drupalLogin($this->root_user);
  }

  /**
   * Test disabled custom links.
   */
  function testCustomDisabled() {
    $type = $this->drupalCreateContentType();
    $node = $this->drupalCreateNode(['type' => $type->type]);
    $text = $this->randomName();
    $xpath = $this->buildXPathQuery('//div[@id=:id]//a[contains(text(), :text)]', [
      ':id' => 'admin-menu',
      ':text' => $text,
    ]);

    // Verify that the link does not appear in the menu.
    $this->drupalGet('node');
    $elements = $this->xpath($xpath);
    $this->assertFalse($elements, 'Custom link not found.');

    // Add a custom link to the node to the menu.
    $edit = [
      'link_path' => 'node/' . $node->nid,
      'link_title' => $text,
      'parent' => 'admin:' . $this->queryMlidByPath('admin'),
    ];
    $this->drupalPost('admin/structure/menu/manage/admin/add', $edit, t('Save'));

    // Verify that the link appears in the menu.
    $this->drupalGet('node');
    $elements = $this->xpath($xpath);
    $this->assertTrue($elements, 'Custom link found.');

    // Disable the link.
    $edit = [
      'enabled' => FALSE,
    ];
    $this->drupalPost('admin/structure/menu/item/' . $this->queryMlidByPath('node/' . $node->nid) . '/edit', $edit, t('Save'));

    // Verify that the disabled link does not appear in the menu.
    $this->drupalGet('node');
    $elements = $this->xpath($xpath);
    $this->assertFalse($elements, 'Disabled custom link not found.');
  }

  /**
   * Tests external links.
   */
  function testCustomExternal() {
    // Add a custom link to the node to the menu.
    $edit = [
      'link_path' => 'http://example.com',
      'link_title' => 'Example',
      'parent' => 'admin:' . $this->queryMlidByPath('admin'),
    ];
    $this->drupalPost('admin/structure/menu/manage/admin/add', $edit, t('Save'));

    // Verify that the link appears in the menu.
    $this->drupalGet('');
    $elements = $this->xpath('//div[@id=:id]//a[@href=:href and contains(text(), :text)]', [
      ':id' => 'admin-menu',
      ':href' => $edit['link_path'],
      ':text' => $edit['link_title'],
    ]);
    $this->assertTrue($elements, 'External link found.');
  }

  /**
   * Returns the menu link ID for a given link path in the admin menu.
   */
  protected function queryMlidByPath($path) {
    return db_query('SELECT mlid FROM {menu_links} WHERE menu_name = :menu AND link_path = :path', [
      ':menu' => 'admin',
      ':path' => $path,
    ])->fetchField();
  }
}

