<?php

namespace Drupal\entity_gallery\Tests;

use Drupal\Core\Url;
use Drupal\entity_gallery\Entity\EntityGallery;
use Drupal\entity_gallery\Entity\EntityGalleryType;

/**
 * Tests the UI for controlling entity gallery revision behavior.
 *
 * @group entity_gallery
 */
class EntityGalleryRevisionsUiTest extends EntityGalleryTestBase {

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $editor;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create users.
    $this->editor = $this->drupalCreateUser([
      'administer entity galleries',
      'edit any page entity galleries',
      'view page revisions',
      'access user profiles',
    ]);
  }

  /**
   * Checks that unchecking 'Create new revision' works when editing an entity
   * gallery.
   */
  function testEntityGalleryFormSaveWithoutRevision() {
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
    $this->drupalPostForm('gallery/' . $entity_gallery->id() . '/edit', $edit, t('Save and keep published'));

    // Load the entity gallery again and check the revision is the same as before.
    $entity_gallery_storage->resetCache(array($entity_gallery->id()));
    $entity_gallery_revision = $entity_gallery_storage->load($entity_gallery->id(), TRUE);
    $this->assertEqual($entity_gallery_revision->getRevisionId(), $entity_gallery->getRevisionId(), "After an existing entity gallery is saved with 'Create new revision' unchecked, a new revision is not created.");

    // Verify the checkbox is checked on the entity gallery edit form.
    $this->drupalGet('gallery/' . $entity_gallery->id() . '/edit');
    $this->assertFieldChecked('edit-revision', "'Create new revision' checkbox is checked");

    // Submit the form without changing the checkbox.
    $edit = array();
    $this->drupalPostForm('gallery/' . $entity_gallery->id() . '/edit', $edit, t('Save and keep published'));

    // Load the entity gallery again and check the revision is different from
    // before.
    $entity_gallery_storage->resetCache(array($entity_gallery->id()));
    $entity_gallery_revision = $entity_gallery_storage->load($entity_gallery->id());
    $this->assertNotEqual($entity_gallery_revision->getRevisionId(), $entity_gallery->getRevisionId(), "After an existing entity gallery is saved with 'Create new revision' checked, a new revision is created.");
  }

  /**
   * Checks HTML double escaping of revision logs.
   */
  public function testEntityGalleryRevisionDoubleEscapeFix() {
    $this->drupalLogin($this->editor);
    $entity_galleries = [];

    // Create the entity gallery.
    $entity_gallery = $this->drupalCreateEntityGallery();

    $username = [
      '#theme' => 'username',
      '#account' => $this->editor,
    ];
    $editor = \Drupal::service('renderer')->renderPlain($username);

    // Get original entity gallery.
    $entity_galleries[] = clone $entity_gallery;

    // Create revision with a random title and gallery items and update
    // variables.
    $entity_gallery->title = $this->randomMachineName();
    $node = $this->drupalCreateNode();
    $entity_gallery->entity_gallery_node->target_id = $node->label();
    $entity_gallery->setNewRevision();
    $revision_log = 'Revision <em>message</em> with markup.';
    $entity_gallery->revision_log->value = $revision_log;
    $entity_gallery->save();
    // Make sure we get revision information.
    $entity_gallery = EntityGallery::load($entity_gallery->id());
    $entity_galleries[] = clone $entity_gallery;

    $this->drupalGet('gallery/' . $entity_gallery->id() . '/revisions');

    // Assert the old revision message.
    $date = format_date($entity_galleries[0]->revision_timestamp->value, 'short');
    $url = new Url('entity.entity_gallery.revision', ['entity_gallery' => $entity_galleries[0]->id(), 'entity_gallery_revision' => $entity_galleries[0]->getRevisionId()]);
    $this->assertRaw(\Drupal::l($date, $url) . ' by ' . $editor);

    // Assert the current revision message.
    $date = format_date($entity_galleries[1]->revision_timestamp->value, 'short');
    $this->assertRaw($entity_galleries[1]->link($date) . ' by ' . $editor . '<p class="revision-log">' . $revision_log . '</p>');
  }

}
