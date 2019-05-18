<?php

namespace Drupal\entity_gallery\Tests;

use Drupal\entity_gallery\Entity\EntityGallery;

/**
 * Tests $entity_gallery->save() for saving entity galleries.
 *
 * @group entity_gallery
 */
class EntityGallerySaveTest extends EntityGalleryTestBase {

  /**
   * A normal logged in user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_gallery_test');

  protected function setUp() {
    parent::setUp();

    // Create a user that is allowed to post; we'll use this to test the submission.
    $web_user = $this->drupalCreateUser(array('create article entity galleries'));
    $this->drupalLogin($web_user);
    $this->webUser = $web_user;
  }

  /**
   * Checks whether custom entity gallery IDs are saved properly during an
   * import operation.
   *
   * Workflow:
   *  - first create an entity gallery
   *  - save the entity gallery
   *  - check if entity gallery exists
   */
  function testImport() {
    // Entity gallery ID must be a number that is not in the database.
    $egids = \Drupal::entityManager()->getStorage('entity_gallery')->getQuery()
      ->sort('egid', 'DESC')
      ->range(0, 1)
      ->execute();
    $max_egid = reset($egids);
    $test_egid = $max_egid + mt_rand(1000, 1000000);
    $title = $this->randomMachineName(8);
    $entity_gallery = array(
      'title' => $title,
      'uid' => $this->webUser->id(),
      'type' => 'article',
      'egid' => $test_egid,
    );
    /** @var \Drupal\entity_gallery\EntityGalleryInterface $entity_gallery */
    $entity_gallery = EntityGallery::create($entity_gallery);
    $entity_gallery->enforceIsNew();

    $this->assertEqual($entity_gallery->getOwnerId(), $this->webUser->id());

    $entity_gallery->save();
    // Test the import.
    $entity_gallery_by_egid = EntityGallery::load($test_egid);
    $this->assertTrue($entity_gallery_by_egid, 'Entity gallery load by entity gallery ID.');

    $entity_gallery_by_title = $this->drupalGetEntityGalleryByTitle($title);
    $this->assertTrue($entity_gallery_by_title, 'Entity gallery load by entity gallery title.');
  }

  /**
   * Verifies accuracy of the "created" and "changed" timestamp functionality.
   */
  function testTimestamps() {
    // Use the default timestamps.
    $edit = array(
      'uid' => $this->webUser->id(),
      'type' => 'article',
      'title' => $this->randomMachineName(8),
    );

    EntityGallery::create($edit)->save();
    $entity_gallery = $this->drupalGetEntityGalleryByTitle($edit['title']);
    $this->assertEqual($entity_gallery->getCreatedTime(), REQUEST_TIME, 'Creating an entity gallery sets default "created" timestamp.');
    $this->assertEqual($entity_gallery->getChangedTime(), REQUEST_TIME, 'Creating an entity gallery sets default "changed" timestamp.');

    // Store the timestamps.
    $created = $entity_gallery->getCreatedTime();

    $entity_gallery->save();
    $entity_gallery = $this->drupalGetEntityGalleryByTitle($edit['title'], TRUE);
    $this->assertEqual($entity_gallery->getCreatedTime(), $created, 'Updating an entity gallery preserves "created" timestamp.');

    // Programmatically set the timestamps using hook_ENTITY_TYPE_presave().
    $entity_gallery->title = 'testing_entity_gallery_presave';

    $entity_gallery->save();
    $entity_gallery = $this->drupalGetEntityGalleryByTitle('testing_entity_gallery_presave', TRUE);
    $this->assertEqual($entity_gallery->getCreatedTime(), 280299600, 'Saving an entity gallery uses "created" timestamp set in presave hook.');
    $this->assertEqual($entity_gallery->getChangedTime(), 979534800, 'Saving an entity gallery uses "changed" timestamp set in presave hook.');

    // Programmatically set the timestamps on the entity gallery.
    $edit = array(
      'uid' => $this->webUser->id(),
      'type' => 'article',
      'title' => $this->randomMachineName(8),
      'created' => 280299600, // Sun, 19 Nov 1978 05:00:00 GMT
      'changed' => 979534800, // Drupal 1.0 release.
    );

    EntityGallery::create($edit)->save();
    $entity_gallery = $this->drupalGetEntityGalleryByTitle($edit['title']);
    $this->assertEqual($entity_gallery->getCreatedTime(), 280299600, 'Creating an entity gallery programmatically uses programmatically set "created" timestamp.');
    $this->assertEqual($entity_gallery->getChangedTime(), 979534800, 'Creating an entity gallery programmatically uses programmatically set "changed" timestamp.');

    // Update the timestamps.
    $entity_gallery->setCreatedTime(979534800);
    $entity_gallery->changed = 280299600;

    $entity_gallery->save();
    $entity_gallery = $this->drupalGetEntityGalleryByTitle($edit['title'], TRUE);
    $this->assertEqual($entity_gallery->getCreatedTime(), 979534800, 'Updating an entity gallery uses user-set "created" timestamp.');
    // Allowing setting changed timestamps is required, see
    // Drupal\content_translation\ContentTranslationMetadataWrapper::setChangedTime($timestamp)
    // for example.
    $this->assertEqual($entity_gallery->getChangedTime(), 280299600, 'Updating an entity gallery uses user-set "changed" timestamp.');
  }

  /**
   * Tests entity gallery presave and static entity gallery load cache.
   *
   * This test determines changes in hook_ENTITY_TYPE_presave() and verifies
   * that the static entity gallery load cache is cleared upon save.
   */
  function testDeterminingChanges() {
    // Initial creation.
    $entity_gallery = EntityGallery::create([
      'uid' => $this->webUser->id(),
      'type' => 'article',
      'title' => 'test_changes',
    ]);
    $entity_gallery->save();

    // Update the entity gallery without applying changes.
    $entity_gallery->save();
    $this->assertEqual($entity_gallery->label(), 'test_changes', 'No changes have been determined.');

    // Apply changes.
    $entity_gallery->title = 'updated';
    $entity_gallery->save();

    // The hook implementations entity_gallery_test_entity_gallery_presave() and
    // entity_gallery_test_entity_gallery_update() determine changes and change
    // the title.
    $this->assertEqual($entity_gallery->label(), 'updated_presave_update', 'Changes have been determined.');

    // Test the static entity gallery load cache to be cleared.
    $entity_gallery = EntityGallery::load($entity_gallery->id());
    $this->assertEqual($entity_gallery->label(), 'updated_presave', 'Static cache has been cleared.');
  }

  /**
   * Tests saving an entity gallery on entity gallery insert.
   *
   * This test ensures that an entity gallery has been fully saved when
   * hook_ENTITY_TYPE_insert() is invoked, so that the entity gallery can be
   * saved again in a hook implementation without errors.
   *
   * @see entity_gallery_test_entity_gallery_insert()
   */
  function testEntityGallerySaveOnInsert() {
    // entity_gallery_test_entity_gallery_insert() triggers a save on insert if the title equals
    // 'new'.
    $entity_gallery = $this->drupalCreateEntityGallery(array('title' => 'new'));
    $this->assertEqual($entity_gallery->getTitle(), 'Entity Gallery ' . $entity_gallery->id(), 'Entity gallery saved on entity gallery insert.');
  }
}
