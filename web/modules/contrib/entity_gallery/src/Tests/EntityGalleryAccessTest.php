<?php

namespace Drupal\entity_gallery\Tests;

use Drupal\user\RoleInterface;

/**
 * Tests basic entity_gallery_access functionality.
 *
 * Note that hook_entity_gallery_access_records() is covered in another test class.
 *
 * @group entity_gallery
 * @todo Cover hook_entity_gallery_access in a separate test class.
 */
class EntityGalleryAccessTest extends EntityGalleryTestBase {
  protected function setUp() {
    parent::setUp();
    // Clear permissions for authenticated users.
    $this->config('user.role.' . RoleInterface::AUTHENTICATED_ID)->set('permissions', array())->save();
  }

  /**
   * Runs basic tests for entity_gallery_access function.
   */
  function testEntityGalleryAccess() {
    // Ensures user without 'access entity galleries' permission can do nothing.
    $web_user1 = $this->drupalCreateUser(array('create page entity galleries', 'edit any page entity galleries', 'delete any page entity galleries'));
    $entity_gallery1 = $this->drupalCreateEntityGallery(array('type' => 'page'));
    $this->assertEntityGalleryCreateAccess($entity_gallery1->bundle(), FALSE, $web_user1);
    $this->assertEntityGalleryAccess(array('view' => FALSE, 'update' => FALSE, 'delete' => FALSE), $entity_gallery1, $web_user1);

    // Ensures user with 'bypass entity gallery access' permission can do everything.
    $web_user2 = $this->drupalCreateUser(array('bypass entity gallery access'));
    $entity_gallery2 = $this->drupalCreateEntityGallery(array('type' => 'page'));
    $this->assertEntityGalleryCreateAccess($entity_gallery2->bundle(), TRUE, $web_user2);
    $this->assertEntityGalleryAccess(array('view' => TRUE, 'update' => TRUE, 'delete' => TRUE), $entity_gallery2, $web_user2);

    // User cannot 'view own unpublished entity galleries'.
    $web_user3 = $this->drupalCreateUser(array('access entity galleries'));
    $entity_gallery3 = $this->drupalCreateEntityGallery(array('status' => 0, 'uid' => $web_user3->id()));
    $this->assertEntityGalleryAccess(array('view' => FALSE), $entity_gallery3, $web_user3);

    // User cannot create entity galleries without permission.
    $this->assertEntityGalleryCreateAccess($entity_gallery3->bundle(), FALSE, $web_user3);

    // User can 'view own unpublished entity galleries', but another user cannot.
    $web_user4 = $this->drupalCreateUser(array('access entity galleries', 'view own unpublished entity galleries'));
    $web_user5 = $this->drupalCreateUser(array('access entity galleries', 'view own unpublished entity galleries'));
    $entity_gallery4 = $this->drupalCreateEntityGallery(array('status' => 0, 'uid' => $web_user4->id()));
    $this->assertEntityGalleryAccess(array('view' => TRUE, 'update' => FALSE), $entity_gallery4, $web_user4);
    $this->assertEntityGalleryAccess(array('view' => FALSE), $entity_gallery4, $web_user5);

    // Tests the default access provided for a published entity gallery.
    $entity_gallery5 = $this->drupalCreateEntityGallery();
    $this->assertEntityGalleryAccess(array('view' => TRUE, 'update' => FALSE, 'delete' => FALSE), $entity_gallery5, $web_user3);

    // Tests the "edit any BUNDLE" and "delete any BUNDLE" permissions.
    $web_user6 = $this->drupalCreateUser(array('access entity galleries', 'edit any page entity galleries', 'delete any page entity galleries'));
    $entity_gallery6 = $this->drupalCreateEntityGallery(array('type' => 'page'));
    $this->assertEntityGalleryAccess(array('view' => TRUE, 'update' => TRUE, 'delete' => TRUE), $entity_gallery6, $web_user6);

    // Tests the "edit own BUNDLE" and "delete own BUNDLE" permission.
    $web_user7 = $this->drupalCreateUser(array('access entity galleries', 'edit own page entity galleries', 'delete own page entity galleries'));
    // User should not be able to edit or delete entity galleries they do not own.
    $this->assertEntityGalleryAccess(array('view' => TRUE, 'update' => FALSE, 'delete' => FALSE), $entity_gallery6, $web_user7);

    // User should be able to edit or delete entity galleries they own.
    $entity_gallery7 = $this->drupalCreateEntityGallery(array('type' => 'page', 'uid' => $web_user7->id()));
    $this->assertEntityGalleryAccess(array('view' => TRUE, 'update' => TRUE, 'delete' => TRUE), $entity_gallery7, $web_user7);
  }

}
