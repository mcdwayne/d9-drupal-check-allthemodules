<?php

namespace Drupal\entity_gallery\Tests;

use Drupal\entity_gallery\Entity\EntityGalleryType;

/**
 * Tests the revision tab display.
 *
 * This test is similar to EntityGalleryRevisionsUITest except that it uses a
 * user with the bypass entity gallery access permission to make sure that the
 * revision access check adds correct cacheability metadata.
 *
 * @group entity_gallery
 */
class EntityGalleryRevisionsUiBypassAccessTest extends EntityGalleryTestBase {

  /**
   * User with bypass entity gallery access permission.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $editor;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a user.
    $this->editor = $this->drupalCreateUser([
      'administer entity galleries',
      'edit any page entity galleries',
      'view page revisions',
      'bypass entity gallery access',
      'access user profiles',
    ]);
  }

  /**
   * Checks that the Revision tab is displayed correctly.
   */
  function testDisplayRevisionTab() {
    $this->drupalPlaceBlock('local_tasks_block');

    $this->drupalLogin($this->editor);
    $entity_gallery_storage = $this->container->get('entity.manager')->getStorage('entity_gallery');

    // Set page revision setting 'create new revision'. This will mean new
    // revisions are created by default when the entity gallery is edited.
    $type = EntityGalleryType::load('page');
    $type->setNewRevision(TRUE);
    $type->save();

    // Create the entity gallery.
    $entity_gallery = $this->drupalCreateEntityGallery();

    // Verify the checkbox is checked on the entity gallery edit form.
    $this->drupalGet('gallery/' . $entity_gallery->id() . '/edit');
    $this->assertFieldChecked('edit-revision', "'Create new revision' checkbox is checked");

    // Uncheck the create new revision checkbox and save the entity gallery.
    $edit = array('revision' => FALSE);
    $this->drupalPostForm('gallery/' . $entity_gallery->id() . '/edit', $edit, 'Save and keep published');

    $this->assertUrl($entity_gallery->toUrl());
    $this->assertNoLink(t('Revisions'));

    // Verify the checkbox is checked on the entity gallery edit form.
    $this->drupalGet('gallery/' . $entity_gallery->id() . '/edit');
    $this->assertFieldChecked('edit-revision', "'Create new revision' checkbox is checked");

    // Submit the form without changing the checkbox.
    $edit = array();
    $this->drupalPostForm('gallery/' . $entity_gallery->id() . '/edit', $edit, 'Save and keep published');

    $this->assertUrl($entity_gallery->toUrl());
    $this->assertLink(t('Revisions'));
  }

}
