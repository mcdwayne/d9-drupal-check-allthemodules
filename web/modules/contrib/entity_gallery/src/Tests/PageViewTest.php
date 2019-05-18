<?php

namespace Drupal\entity_gallery\Tests;

use Drupal\entity_gallery\Entity\EntityGallery;

/**
 * Create an entity gallery and test edit permissions.
 *
 * @group entity_gallery
 */
class PageViewTest extends EntityGalleryTestBase {
  /**
   * Tests an anonymous and unpermissioned user attempting to edit the entity
   * gallery.
   */
  function testPageView() {
    // Create an entity gallery to view.
    $entity_gallery = $this->drupalCreateEntityGallery();
    $this->assertTrue(EntityGallery::load($entity_gallery->id()), 'Entity gallery created.');

    // Try to edit with anonymous user.
    $this->drupalGet("gallery/" . $entity_gallery->id() . "/edit");
    $this->assertResponse(403);

    // Create a user without permission to edit entity gallery.
    $web_user = $this->drupalCreateUser(array('access entity galleries'));
    $this->drupalLogin($web_user);

    // Attempt to access edit page.
    $this->drupalGet("gallery/" . $entity_gallery->id() . "/edit");
    $this->assertResponse(403);

    // Create user with permission to edit entity gallery.
    $web_user = $this->drupalCreateUser(array('bypass entity gallery access'));
    $this->drupalLogin($web_user);

    // Attempt to access edit page.
    $this->drupalGet("gallery/" . $entity_gallery->id() . "/edit");
    $this->assertResponse(200);
  }
}
