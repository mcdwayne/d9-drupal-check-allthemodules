<?php

namespace Drupal\entity_gallery\Tests\Views;

/**
 * Tests the entity_gallery.status_extra field handler.
 *
 * @group entity_gallery
 * @see \Drupal\entity_gallery\Plugin\views\filter\Status
 */
class StatusExtraTest extends EntityGalleryTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_status_extra');

  /**
   * Tests the status extra filter.
   */
  public function testStatusExtra() {
    $entity_gallery_author = $this->drupalCreateUser(array('view own unpublished entity galleries'));
    $entity_gallery_author_not_unpublished = $this->drupalCreateUser();
    $normal_user = $this->drupalCreateUser();
    $admin_user = $this->drupalCreateUser(array('bypass entity gallery access'));

    // Create one published and one unpublished entity gallery by the admin.
    $entity_gallery_published = $this->drupalCreateEntityGallery(array('uid' => $admin_user->id()));
    $entity_gallery_unpublished = $this->drupalCreateEntityGallery(array('uid' => $admin_user->id(), 'status' => ENTITY_GALLERY_NOT_PUBLISHED));

    // Create one unpublished entity gallery by a certain author user.
    $entity_gallery_unpublished2 = $this->drupalCreateEntityGallery(array('uid' => $entity_gallery_author->id(), 'status' => ENTITY_GALLERY_NOT_PUBLISHED));

    // Create one unpublished entity gallery by a user who does not have the `view own
    // unpublished content` permission.
    $entity_gallery_unpublished3 = $this->drupalCreateEntityGallery(array('uid' => $entity_gallery_author_not_unpublished->id(), 'status' => ENTITY_GALLERY_NOT_PUBLISHED));

    // The administrator should simply see all entity galleries.
    $this->drupalLogin($admin_user);
    $this->drupalGet('test_status_extra');
    $this->assertText($entity_gallery_published->label());
    $this->assertText($entity_gallery_unpublished->label());
    $this->assertText($entity_gallery_unpublished2->label());
    $this->assertText($entity_gallery_unpublished3->label());

    // The entity gallery author should see the published entity gallery and his
    // own entity gallery.
    $this->drupalLogin($entity_gallery_author);
    $this->drupalGet('test_status_extra');
    $this->assertText($entity_gallery_published->label());
    $this->assertNoText($entity_gallery_unpublished->label());
    $this->assertText($entity_gallery_unpublished2->label());
    $this->assertNoText($entity_gallery_unpublished3->label());

    // The normal user should just see the published entity gallery.
    $this->drupalLogin($normal_user);
    $this->drupalGet('test_status_extra');
    $this->assertText($entity_gallery_published->label());
    $this->assertNoText($entity_gallery_unpublished->label());
    $this->assertNoText($entity_gallery_unpublished2->label());
    $this->assertNoText($entity_gallery_unpublished3->label());

    // The author without the permission to see his own unpublished entity
    // gallery should just see the published entity gallery.
    $this->drupalLogin($entity_gallery_author_not_unpublished);
    $this->drupalGet('test_status_extra');
    $this->assertText($entity_gallery_published->label());
    $this->assertNoText($entity_gallery_unpublished->label());
    $this->assertNoText($entity_gallery_unpublished2->label());
    $this->assertNoText($entity_gallery_unpublished3->label());
  }

}
