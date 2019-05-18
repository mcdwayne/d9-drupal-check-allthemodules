<?php

namespace Drupal\entity_gallery\Tests;

use Drupal\Core\Cache\Cache;

/**
 * Tests changing view modes for entity galleries.
 *
 * @group entity_gallery
 */
class EntityGalleryEntityViewModeAlterTest extends EntityGalleryTestBase {

  /**
   * Enable dummy module that implements hook_ENTITY_TYPE_view() for entity
   * galleries.
   */
  public static $modules = array('entity_gallery_test');

  /**
   * Create a "Basic page" entity gallery and verify its consistency in the
   * database.
   */
  function testEntityGalleryViewModeChange() {
    $web_user = $this->drupalCreateUser(array('create page entity galleries', 'edit own page entity galleries'));
    $this->drupalLogin($web_user);

    // Create a node to be used as gallery content.
    $node = $this->drupalCreateNode();

    // Create an entity gallery.
    $edit = array();
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['entity_gallery_node[0][target_id]'] = $node->label();
    $this->drupalPostForm('gallery/add/page', $edit, t('Save'));

    $entity_gallery = $this->drupalGetEntityGalleryByTitle($edit['title[0][value]']);

    // Set the flag to alter the view mode and view the entity gallery.
    \Drupal::state()->set('entity_gallery_test_change_view_mode', 'teaser');
    Cache::invalidateTags(['rendered']);
    $this->drupalGet('gallery/' . $entity_gallery->id());

    // Test that the correct build mode has been set.
    $build = $this->drupalBuildEntityView($entity_gallery);
    $this->assertEqual($build['#view_mode'], 'teaser', 'The view mode has correctly been set to teaser.');
  }
}
