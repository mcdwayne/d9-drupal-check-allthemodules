<?php

namespace Drupal\entity_gallery\Tests;

use Drupal\Component\Utility\Html;

/**
 * Create an entity gallery with dangerous tags in its title and test that they
 * are escaped.
 *
 * @group entity_gallery
 */
class EntityGalleryTitleXSSTest extends EntityGalleryTestBase {
  /**
   * Tests XSS functionality with an entity gallery entity.
   */
  function testEntityGalleryTitleXSS() {
    // Prepare a user to do the stuff.
    $web_user = $this->drupalCreateUser(array('create page entity galleries', 'edit any page entity galleries'));
    $this->drupalLogin($web_user);

    $xss = '<script>alert("xss")</script>';
    $title = $xss . $this->randomMachineName();
    $edit = array();
    $edit['title[0][value]'] = $title;

    $this->drupalPostForm('gallery/add/page', $edit, t('Preview'));
    $this->assertNoRaw($xss, 'Harmful tags are escaped when previewing an entity gallery.');

    $settings = array('title' => $title);
    $entity_gallery = $this->drupalCreateEntityGallery($settings);

    $this->drupalGet('gallery/' . $entity_gallery->id());
    // Titles should be escaped.
    $this->assertTitle(Html::escape($title) . ' | Drupal', 'Title is displayed when viewing an entity gallery.');
    $this->assertNoRaw($xss, 'Harmful tags are escaped when viewing an entity gallery.');

    $this->drupalGet('gallery/' . $entity_gallery->id() . '/edit');
    $this->assertNoRaw($xss, 'Harmful tags are escaped when editing an entity gallery.');
  }
}
