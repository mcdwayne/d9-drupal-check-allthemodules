<?php

namespace Drupal\entity_gallery\Tests;

/**
 * Tests that the post information (submitted by Username on date) text displays
 * appropriately.
 *
 * @group entity_gallery
 */
class EntityGalleryPostSettingsTest extends EntityGalleryTestBase {

  protected function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(array('create page entity galleries', 'administer entity gallery types', 'access user profiles'));
    $this->drupalLogin($web_user);
  }

  /**
   * Confirms "Basic page" entity gallery type and post information is on a new
   * entity gallery.
   */
  function testPagePostInfo() {

    // Set "Basic page" entity gallery type to display post information.
    $edit = array();
    $edit['display_submitted'] = TRUE;
    $this->drupalPostForm('admin/structure/gallery-types/manage/page', $edit, t('Save gallery type'));

    // Create an entity gallery.
    $edit = array();
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['entity_gallery_node[0][target_id]'] = $this->drupalCreateNode()->label();
    $this->drupalPostForm('gallery/add/page', $edit, t('Save'));

    // Check that the post information is displayed.
    $entity_gallery = $this->drupalGetEntityGalleryByTitle($edit['title[0][value]']);
    $elements = $this->xpath('//a[contains(@class, :class)]', array(':class' => 'username'));
    $this->assertEqual(count($elements), 1, 'Post information is displayed.');
    $entity_gallery->delete();

    // Set "Basic page" entity gallery type to display post information.
    $edit = array();
    $edit['display_submitted'] = FALSE;
    $this->drupalPostForm('admin/structure/gallery-types/manage/page', $edit, t('Save gallery type'));

    // Create an entity gallery.
    $edit = array();
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['entity_gallery_node[0][target_id]'] = $this->drupalCreateNode()->label();
    $this->drupalPostForm('gallery/add/page', $edit, t('Save'));

    // Check that the post information is displayed.
    $elements = $this->xpath('//a[contains(@class, :class)]', array(':class' => 'username'));
    $this->assertEqual(count($elements), 0, 'Post information is not displayed.');
  }
}
