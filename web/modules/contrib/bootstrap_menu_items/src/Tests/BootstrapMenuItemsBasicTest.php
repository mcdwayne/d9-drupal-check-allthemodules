<?php

/**
 * @file
 * Test file for Bootstrap menu items module.
 */

/**
 * Test basic functionality of Bootstrap menu items module.
 *
 * @group Bootstrap menu items
 */
class BootstrapMenuItemsBasicTest extends DrupalWebTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Bootstrap menu items basic tests',
      'description' => 'Test basic functionality of Bootstrap menu items module.',
      'group' => 'Bootstrap menu items',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp('menu', 'bootstrap_menu_items');

    $permissions = array(
      'access administration pages',
      'administer menu',
    );

    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Test custom menu items.
   */
  public function testBootstrapMenuItemsMenuTests($menu_name = 'navigation') {
    // Add nodes to use as links for menu links.
    $node1 = $this->drupalCreateNode(array('type' => 'article'));
    $node2 = $this->drupalCreateNode(array('type' => 'article'));
    $node3 = $this->drupalCreateNode(array('type' => 'article'));
    $node4 = $this->drupalCreateNode(array('type' => 'article'));
    $node5 = $this->drupalCreateNode(array('type' => 'article'));

    // Add menu links.
    $item1 = $this->addMenuLink(0, '<nolink>', $menu_name, TRUE);
    $item2 = $this->addMenuLink($item1['mlid'], 'node/' . $node2->nid, $menu_name);
    $item3 = $this->addMenuLink($item1['mlid'], 'node/' . $node3->nid, $menu_name);
    $item4 = $this->addMenuLink($item1['mlid'], '<separator>', $menu_name);
    $item5 = $this->addMenuLink($item1['mlid'], '<header>', $menu_name);
    $item6 = $this->addMenuLink($item1['mlid'], 'node/' . $node4->nid, $menu_name);
    $item7 = $this->addMenuLink($item1['mlid'], 'node/' . $node5->nid, $menu_name);
    $this->assertMenuLink($item1['mlid'], array(
      'depth' => 1,
      'has_children' => 1,
      'p1' => $item1['mlid'],
      'p2' => 0,
    ));
    $this->assertMenuLink($item2['mlid'], array(
      'depth' => 2,
      'has_children' => 0,
      'p1' => $item1['mlid'],
      'p2' => $item2['mlid'],
      'p3' => 0,
    ));
    $this->assertMenuLink($item3['mlid'], array(
      'depth' => 2,
      'has_children' => 0,
      'p1' => $item1['mlid'],
      'p2' => $item3['mlid'],
      'p3' => 0,
    ));
    $this->assertMenuLink($item4['mlid'], array(
      'depth' => 2,
      'has_children' => 0,
      'p1' => $item1['mlid'],
      'p2' => $item4['mlid'],
      'p3' => 0,
    ));
    $this->assertMenuLink($item5['mlid'], array(
      'depth' => 2,
      'has_children' => 0,
      'p1' => $item1['mlid'],
      'p2' => $item5['mlid'],
      'p3' => 0,
    ));
    $this->assertMenuLink($item6['mlid'], array(
      'depth' => 2,
      'has_children' => 0,
      'p1' => $item1['mlid'],
      'p2' => $item6['mlid'],
      'p3' => 0,
    ));
    $this->assertMenuLink($item6['mlid'], array(
      'depth' => 2,
      'has_children' => 0,
      'p1' => $item1['mlid'],
      'p2' => $item6['mlid'],
      'p3' => 0,
    ));

    $this->drupalGet('');
    $this->assertTrue($this->xpath('//a[@href="#" and contains(@class, :class)]', array(':class' => 'nolink')), 'Top level menu link found.');
    $this->assertTrue($this->xpath('//li[contains(@class, :class)]', array(':class' => 'dropdown-header')), 'Menu header found.');
    $this->assertTrue($this->xpath('//li[@role=:role and contains(@class, :class)]', array(':role' => 'separator', ':class' => 'divider')), 'Menu separator found.');
    $this->assertFieldByXPath('//li[contains(@class, "dropdown-header")]', $item5['link_title'], 'Menu header name found.');
    $this->assertFieldByXPath('//li[@role="separator" and contains(@class, "divider")]', $item4['link_title'], 'Menu separator name found.');
  }

  /**
   * CORE menu module clone.
   *
   * Add a menu link using the menu module UI.
   *
   * @param int $plid
   *   Parent menu link id.
   * @param string $link
   *   Link path.
   * @param string $menu_name
   *   Menu name.
   * @param bool $expanded
   *   Menu expanded or not.
   *
   * @return array
   *   Menu link created.
   */
  public function addMenuLink($plid = 0, $link = '<front>', $menu_name = 'navigation', $expanded = TRUE) {
    // View add menu link page.
    $this->drupalGet("admin/structure/menu/manage/$menu_name/add");
    $this->assertResponse(200);

    $title = '!link_' . $this->randomName(16);
    $edit = array(
      'link_path' => $link,
      'link_title' => $title,
      // Use this to disable the menu and test.
      'description' => '',
      'enabled' => TRUE,
      // Setting this to true should test whether it works when we do the
      // std_user tests.
      'expanded' => $expanded,
      'parent' => $menu_name . ':' . $plid,
      'weight' => '0',
    );

    // Add menu link.
    $this->drupalPost(NULL, $edit, t('Save'));
    $this->assertResponse(200);
    // Unlike most other modules, there is no confirmation message displayed.
    $this->assertText($title, 'Menu link was added');

    $item = db_query('SELECT * FROM {menu_links} WHERE link_title = :title', array(':title' => $title))->fetchAssoc();
    $this->assertTrue(t('Menu link was found in database.'));
    $this->assertMenuLink($item['mlid'], array('menu_name' => $menu_name, 'link_path' => $link, 'has_children' => 0, 'plid' => $plid));

    return $item;
  }

  /**
   * CORE menu module clone.
   *
   * Fetch the menu item from the database and compare it to the specified
   * array.
   *
   * @param int $mlid
   *   Menu item id.
   * @param array $expected_item
   *   Array containing properties to verify.
   */
  public function assertMenuLink($mlid, array $expected_item) {
    // Retrieve menu link.
    $item = db_query('SELECT * FROM {menu_links} WHERE mlid = :mlid', array(':mlid' => $mlid))->fetchAssoc();
    $options = unserialize($item['options']);
    if (!empty($options['query'])) {
      $item['link_path'] .= '?' . drupal_http_build_query($options['query']);
    }
    if (!empty($options['fragment'])) {
      $item['link_path'] .= '#' . $options['fragment'];
    }
    foreach ($expected_item as $key => $value) {
      $this->assertEqual($item[$key], $value, format_string('Parameter %key had expected value.', array('%key' => $key)));
    }
  }

}
