<?php

namespace Drupal\site_map\Tests;

/**
 * Test case class for site map's content tests.
 *
 * @group site_map
 */
class SiteMapContentTest extends SiteMapTestBase {

  /**
   * Tests page title.
   */
  public function testPageTitle() {
    // Assert default page title.
    $this->drupalGet('/sitemap');
    $this->assertTitle('Site map | Drupal', 'The title on the site map page is "Site map | Drupal".');

    // Change page title.
    $new_title = $this->randomMachineName();
    $edit = array(
      'page_title' => $new_title,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that page title is changed.
    $this->drupalGet('/sitemap');
    $this->assertTitle("$new_title | Drupal", 'The title on the site map page is "' . "$new_title | Drupal" . '".');
  }

  /**
   * Tests site map message.
   */
  public function testSiteMapMessage() {
    // Assert that site map message is not included in the site map by default.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect('.site-map-message');
    $this->assertEqual(count($elements), 0, 'Site map message is not included.');

    // Change site map message.
    $new_message = $this->randomMachineName(16);
    $edit = array(
      'message[value]' => $new_message,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert site map message is included in the site map.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect(".site-map-message:contains('" . $new_message . "')");
    $this->assertEqual(count($elements), 1, 'Site map message is included.');
  }

  /**
   * Tests front page.
   */
  public function testFrontPage() {
    // Assert that front page is included in the site map by default.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect(".site-map-box h2:contains('" . t('Front page') . "')");
    $this->assertEqual(count($elements), 1, 'Front page is included.');

    // Configure module to hide front page.
    $edit = array(
      'show_front' => FALSE,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that front page is not included in the site map.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect(".site-map-box h2:contains('" . t('Front page') . "')");
    $this->assertEqual(count($elements), 0, 'Front page is not included.');
  }

  /**
   * Tests titles.
   */
  public function testTitles() {
    // Assert that titles are included in the site map by default.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect('.site-map-box h2');
    $this->assertTrue(count($elements) > 0, 'Titles are included.');

    // Configure module to hide titles.
    $edit = array(
      'show_titles' => FALSE,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that titles are not included in the site map.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect('.site-map-box h2');
    $this->assertEqual(count($elements), 0, 'Section titles are not included.');
  }

  /**
   * Tests menus.
   */
  public function testMenus() {
    // Assert that main menu is not included in the site map by default.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect(".site-map-box h2:contains('" . t('Main navigation') . "')");
    $this->assertEqual(count($elements), 0, 'Main menu is not included.');

    // Configure module to show main menu, with enabled menu items only.
    $edit = array(
      'show_menus[main]' => 'main',
      'show_menus_hidden' => FALSE,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Create dummy node with enabled menu item.
    $node_1_title = $this->randomString();
    $edit = array(
      'title[0][value]' => $node_1_title,
      'menu[enabled]' => TRUE,
      'menu[title]' => $node_1_title,
      // In oder to make main navigation menu displayed, there must be at least
      // one child menu item of that menu.
      'menu[menu_parent]' => 'main:',
    );
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));

    // Create dummy node with disabled menu item.
    $node_2_title = $this->randomString();
    $edit = array(
      'title[0][value]' => $node_2_title,
      'menu[enabled]' => TRUE,
      'menu[title]' => $node_2_title,
      'menu[menu_parent]' => 'main:',
    );
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));

    // Disable menu item.
    $menu_links = entity_load_multiple_by_properties('menu_link_content', array('title' => $node_2_title));
    $menu_link = reset($menu_links);
    $mlid = $menu_link->id();
    $edit = array(
      'enabled[value]' => FALSE,
    );
    $this->drupalPostForm("admin/structure/menu/item/$mlid/edit", $edit, t('Save'));

    // Assert that main menu is included in the site map.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect(".site-map-box h2:contains('" . t('Main navigation') . "')");
    $this->assertEqual(count($elements), 1, 'Main menu is included.');

    // Assert that node 1 is listed in the site map, but not node 2.
    $this->assertLink($node_1_title);
    $this->assertNoLink($node_2_title);

    // Configure module to show all menu items.
    $edit = array(
      'show_menus_hidden' => TRUE,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that both node 1 and node 2 are listed in the site map.
    $this->drupalGet('/sitemap');
    $this->assertLink($node_1_title);
    $this->assertLink($node_2_title);
  }

  /**
   * Tests categories.
   */
  public function testCategories() {
    $tags = $this->getTags();
    $vocabulary = $this->createVocabulary();
    $field_tags_name = $this->createTaxonomyTermReferenceField($vocabulary);

    // Assert that the category is not included in the site map by default.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect(".site-map-box h2:contains('" . $vocabulary->label() . "')");
    $this->assertEqual(count($elements), 0, 'Tags category is not included.');

    // Assert that no tags are listed in the site map.
    foreach ($tags as $tag) {
      $this->assertNoLink($tag);
    }

    // Configure module to show categories.
    $vid = $vocabulary->id();
    $edit = array(
      "show_vocabularies[$vid]" => $vid,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Create dummy node.
    $title = $this->randomString();
    $edit = array(
      'title[0][value]' => $title,
      'menu[enabled]' => TRUE,
      'menu[title]' => $title,
      $field_tags_name => implode(',', $tags),
    );
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));

    // Assert that the category is included in the site map.
    $this->drupalGet('sitemap');
    $elements = $this->cssSelect(".site-map-box h2:contains('" . $vocabulary->label() . "')");
    $this->assertEqual(count($elements), 1, 'Tags category is included.');

    // Assert that all tags are listed in the site map.
    foreach ($tags as $tag) {
      $this->assertLink($tag);
    }
  }
}
