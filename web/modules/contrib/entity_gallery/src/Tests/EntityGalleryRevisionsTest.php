<?php

namespace Drupal\entity_gallery\Tests;

use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\entity_gallery\Entity\EntityGallery;
use Drupal\entity_gallery\EntityGalleryInterface;

/**
 * Create an entity gallery with revisions and test viewing, saving, reverting,
 * and deleting revisions for users with access for this entity gallery type.
 *
 * @group entity_gallery
 */
class EntityGalleryRevisionsTest extends EntityGalleryTestBase {

  /**
   * An array of entity gallery revisions.
   *
   * @var \Drupal\entity_gallery\EntityGalleryInterface[]
   */
  protected $entity_galleries;

  /**
   * Revision log messages.
   *
   * @var array
   */
  protected $revisionLogs;

  /**
   * {@inheritdoc}
   */
  public static $modules = array('entity_gallery', 'datetime', 'language', 'content_translation');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    ConfigurableLanguage::createFromLangcode('it')->save();

    $field_storage_definition = array(
      'field_name' => 'untranslatable_string_field',
      'entity_type' => 'entity_gallery',
      'type' => 'string',
      'cardinality' => 1,
      'translatable' => FALSE,
    );
    $field_storage = FieldStorageConfig::create($field_storage_definition);
    $field_storage->save();

    $field_definition = array(
      'field_storage' => $field_storage,
      'bundle' => 'page',
    );
    $field = FieldConfig::create($field_definition);
    $field->save();

    // Create and log in user.
    $web_user = $this->drupalCreateUser(
      array(
        'view page revisions',
        'revert page revisions',
        'delete page revisions',
        'edit any page entity galleries',
        'delete any page entity galleries',
        'translate any entity',
      )
    );

    $this->drupalLogin($web_user);

    // Create initial entity gallery.
    $entity_gallery = $this->drupalCreateEntityGallery();
    $settings = get_object_vars($entity_gallery);
    $settings['revision'] = 1;
    $settings['isDefaultRevision'] = TRUE;

    $entity_galleries = array();
    $logs = array();

    // Get original entity gallery.
    $entity_galleries[] = clone $entity_gallery;

    // Create three revisions.
    $revision_count = 3;
    for ($i = 0; $i < $revision_count; $i++) {
      $logs[] = $entity_gallery->revision_log = $this->randomMachineName(32);

      // Create revision with a random title and body and update variables.
      $entity_gallery->title = $this->randomMachineName();
      $entity_gallery->body = array(
        'value' => $this->randomMachineName(32),
        'format' => filter_default_format(),
      );
      $entity_gallery->untranslatable_string_field->value = $this->randomString();
      $entity_gallery->setNewRevision();

      // Edit the 2nd revision with a different user.
      if ($i == 1) {
        $editor = $this->drupalCreateUser();
        $entity_gallery->setRevisionUserId($editor->id());
      }
      else {
        $entity_gallery->setRevisionUserId($web_user->id());
      }

      $entity_gallery->save();

      $entity_gallery = EntityGallery::load($entity_gallery->id()); // Make sure we get revision information.
      $entity_galleries[] = clone $entity_gallery;
    }

    $this->entity_galleries = $entity_galleries;
    $this->revisionLogs = $logs;
  }

  /**
   * Checks entity gallery revision related operations.
   */
  function testRevisions() {
    $entity_gallery_storage = $this->container->get('entity.manager')->getStorage('entity_gallery');
    $entity_galleries = $this->entity_galleries;
    $logs = $this->revisionLogs;

    // Get last entity gallery for simple checks.
    $entity_gallery = $entity_galleries[3];

    // Confirm the correct revision text appears on "view revisions" page.
    $this->drupalGet("gallery/" . $entity_gallery->id() . "/revisions/" . $entity_gallery->getRevisionId() . "/view");
    $this->assertText($entity_gallery->body->value, 'Correct text displays for version.');

    // Confirm the correct log message appears on "revisions overview" page.
    $this->drupalGet("gallery/" . $entity_gallery->id() . "/revisions");
    foreach ($logs as $revision_log) {
      $this->assertText($revision_log, 'Revision log message found.');
    }
    // Original author, and editor names should appear on revisions overview.
    $web_user = $entity_galleries[0]->revision_uid->entity;
    $this->assertText(t('by @name', ['@name' => $web_user->getAccountName()]));
    $editor = $entity_galleries[2]->revision_uid->entity;
    $this->assertText(t('by @name', ['@name' => $editor->getAccountName()]));

    // Confirm that this is the default revision.
    $this->assertTrue($entity_gallery->isDefaultRevision(), 'Third entity gallery revision is the default one.');

    // Confirm that revisions revert properly.
    $this->drupalPostForm("gallery/" . $entity_gallery->id() . "/revisions/" . $entity_galleries[1]->getRevisionid() . "/revert", array(), t('Revert'));
    $this->assertRaw(t('@type %title has been reverted to the revision from %revision-date.',
                        array('@type' => 'Basic page', '%title' => $entity_galleries[1]->label(),
                              '%revision-date' => format_date($entity_galleries[1]->getRevisionCreationTime()))), 'Revision reverted.');
    $entity_gallery_storage->resetCache(array($entity_gallery->id()));
    $reverted_entity_gallery = $entity_gallery_storage->load($entity_gallery->id());
    $this->assertTrue(($entity_galleries[1]->body->value == $reverted_entity_gallery->body->value), 'Entity gallery reverted correctly.');

    // Confirm that this is not the default version.
    $entity_gallery = entity_gallery_revision_load($entity_gallery->getRevisionId());
    $this->assertFalse($entity_gallery->isDefaultRevision(), 'Third entity gallery revision is not the default one.');

    // Confirm revisions delete properly.
    $this->drupalPostForm("gallery/" . $entity_gallery->id() . "/revisions/" . $entity_galleries[1]->getRevisionId() . "/delete", array(), t('Delete'));
    $this->assertRaw(t('Revision from %revision-date of @type %title has been deleted.',
                        array('%revision-date' => format_date($entity_galleries[1]->getRevisionCreationTime()),
                              '@type' => 'Basic page', '%title' => $entity_galleries[1]->label())), 'Revision deleted.');
    $this->assertTrue(db_query('SELECT COUNT(vid) FROM {entity_gallery_revision} WHERE egid = :egid and vid = :vid', array(':egid' => $entity_gallery->id(), ':vid' => $entity_galleries[1]->getRevisionId()))->fetchField() == 0, 'Revision not found.');

    // Set the revision timestamp to an older date to make sure that the
    // confirmation message correctly displays the stored revision date.
    $old_revision_date = REQUEST_TIME - 86400;
    db_update('entity_gallery_revision')
      ->condition('vid', $entity_galleries[2]->getRevisionId())
      ->fields(array(
        'revision_timestamp' => $old_revision_date,
      ))
      ->execute();
    $this->drupalPostForm("gallery/" . $entity_gallery->id() . "/revisions/" . $entity_galleries[2]->getRevisionId() . "/revert", array(), t('Revert'));
    $this->assertRaw(t('@type %title has been reverted to the revision from %revision-date.', array(
      '@type' => 'Basic page',
      '%title' => $entity_galleries[2]->label(),
      '%revision-date' => format_date($old_revision_date),
    )));

    // Make a new revision and set it to not be default.
    // This will create a new revision that is not "front facing".
    $new_entity_gallery_revision = clone $entity_gallery;
    $new_body = $this->randomMachineName();
    $new_entity_gallery_revision->body->value = $new_body;
    // Save this as a non-default revision.
    $new_entity_gallery_revision->setNewRevision();
    $new_entity_gallery_revision->isDefaultRevision = FALSE;
    $new_entity_gallery_revision->save();

    $this->drupalGet('gallery/' . $entity_gallery->id());
    $this->assertNoText($new_body, 'Revision body text is not present on default version of entity gallery.');

    // Verify that the new body text is present on the revision.
    $this->drupalGet("gallery/" . $entity_gallery->id() . "/revisions/" . $new_entity_gallery_revision->getRevisionId() . "/view");
    $this->assertText($new_body, 'Revision body text is present when loading specific revision.');

    // Verify that the non-default revision vid is greater than the default
    // revision vid.
    $default_revision = db_select('entity_gallery', 'eg')
      ->fields('eg', array('vid'))
      ->condition('egid', $entity_gallery->id())
      ->execute()
      ->fetchCol();
    $default_revision_vid = $default_revision[0];
    $this->assertTrue($new_entity_gallery_revision->getRevisionId() > $default_revision_vid, 'Revision vid is greater than default revision vid.');
  }

  /**
   * Checks that revisions are correctly saved without log messages.
   */
  function testEntityGalleryRevisionWithoutLogMessage() {
    $entity_gallery_storage = $this->container->get('entity.manager')->getStorage('entity_gallery');
    // Create an entity gallery with an initial log message.
    $revision_log = $this->randomMachineName(10);
    $entity_gallery = $this->drupalCreateEntityGallery(array('revision_log' => $revision_log));

    // Save over the same revision and explicitly provide an empty log message
    // (for example, to mimic the case of an entity gallery form submitted with
    // no text in the "log message" field), and check that the original log
    // message is preserved.
    $new_title = $this->randomMachineName(10) . 'testEntityGalleryRevisionWithoutLogMessage1';

    $entity_gallery = clone $entity_gallery;
    $entity_gallery->title = $new_title;
    $entity_gallery->revision_log = '';
    $entity_gallery->setNewRevision(FALSE);

    $entity_gallery->save();
    $this->drupalGet('gallery/' . $entity_gallery->id());
    $this->assertText($new_title, 'New entity gallery title appears on the page.');
    $entity_gallery_storage->resetCache(array($entity_gallery->id()));
    $entity_gallery_revision = $entity_gallery_storage->load($entity_gallery->id());
    $this->assertEqual($entity_gallery_revision->revision_log->value, $revision_log, 'After an existing entity gallery revision is re-saved without a log message, the original log message is preserved.');

    // Create another entity gallery with an initial revision log message.
    $entity_gallery = $this->drupalCreateEntityGallery(array('revision_log' => $revision_log));

    // Save a new entity gallery revision without providing a log message, and
    // check that this revision has an empty log message.
    $new_title = $this->randomMachineName(10) . 'testEntityGalleryRevisionWithoutLogMessage2';

    $entity_gallery = clone $entity_gallery;
    $entity_gallery->title = $new_title;
    $entity_gallery->setNewRevision();
    $entity_gallery->revision_log = NULL;

    $entity_gallery->save();
    $this->drupalGet('gallery/' . $entity_gallery->id());
    $this->assertText($new_title, 'New entity gallery title appears on the page.');
    $entity_gallery_storage->resetCache(array($entity_gallery->id()));
    $entity_gallery_revision = $entity_gallery_storage->load($entity_gallery->id());
    $this->assertTrue(empty($entity_gallery_revision->revision_log->value), 'After a new entity gallery revision is saved with an empty log message, the log message for the entity gallery is empty.');
  }

  /**
   * Tests the revision translations are correctly reverted.
   */
  public function testRevisionTranslationRevert() {
    // Create an entity gallery and a few revisions.
    $entity_gallery = $this->drupalCreateEntityGallery(['langcode' => 'en']);

    $initial_revision_id = $entity_gallery->getRevisionId();
    $initial_title = $entity_gallery->label();
    $this->createRevisions($entity_gallery, 2);

    // Translate the entity gallery and create a few translation revisions.
    $translation = $entity_gallery->addTranslation('it');
    $this->createRevisions($translation, 3);
    $revert_id = $entity_gallery->getRevisionId();
    $translated_title = $translation->label();
    $untranslatable_string = $entity_gallery->untranslatable_string_field->value;

    // Create a new revision for the default translation in-between a series of
    // translation revisions.
    $this->createRevisions($entity_gallery, 1);
    $default_translation_title = $entity_gallery->label();

    // And create a few more translation revisions.
    $this->createRevisions($translation, 2);
    $translation_revision_id = $translation->getRevisionId();

    // Now revert the a translation revision preceding the last default
    // translation revision, and check that the desired value was reverted but
    // the default translation value was preserved.
    $revert_translation_url = Url::fromRoute('entity_gallery.revision_revert_translation_confirm', [
      'entity_gallery' => $entity_gallery->id(),
      'entity_gallery_revision' => $revert_id,
      'langcode' => 'it',
    ]);
    $this->drupalPostForm($revert_translation_url, [], t('Revert'));
    /** @var \Drupal\entity_gallery\EntityGalleryStorage $entity_gallery_storage */
    $entity_gallery_storage = $this->container->get('entity.manager')->getStorage('entity_gallery');
    $entity_gallery_storage->resetCache();
    /** @var \Drupal\entity_gallery\EntityGalleryInterface $entity_gallery */
    $entity_gallery = $entity_gallery_storage->load($entity_gallery->id());
    $this->assertTrue($entity_gallery->getRevisionId() > $translation_revision_id);
    $this->assertEqual($entity_gallery->label(), $default_translation_title);
    $this->assertEqual($entity_gallery->getTranslation('it')->label(), $translated_title);
    $this->assertNotEqual($entity_gallery->untranslatable_string_field->value, $untranslatable_string);

    $latest_revision_id = $translation->getRevisionId();

    // Now revert the a translation revision preceding the last default
    // translation revision again, and check that the desired value was reverted
    // but the default translation value was preserved. But in addition the
    // untranslated field will be reverted as well.
    $this->drupalPostForm($revert_translation_url, ['revert_untranslated_fields' => TRUE], t('Revert'));
    $entity_gallery_storage->resetCache();
    /** @var \Drupal\entity_gallery\EntityGalleryInterface $entity_gallery */
    $entity_gallery = $entity_gallery_storage->load($entity_gallery->id());
    $this->assertTrue($entity_gallery->getRevisionId() > $latest_revision_id);
    $this->assertEqual($entity_gallery->label(), $default_translation_title);
    $this->assertEqual($entity_gallery->getTranslation('it')->label(), $translated_title);
    $this->assertEqual($entity_gallery->untranslatable_string_field->value, $untranslatable_string);

    $latest_revision_id = $translation->getRevisionId();

    // Now revert the entity revision to the initial one where the translation
    // didn't exist.
    $revert_url = Url::fromRoute('entity_gallery.revision_revert_confirm', [
      'entity_gallery' => $entity_gallery->id(),
      'entity_gallery_revision' => $initial_revision_id,
    ]);
    $this->drupalPostForm($revert_url, [], t('Revert'));
    $entity_gallery_storage->resetCache();
    /** @var \Drupal\entity_gallery\EntityGalleryInterface $entity_gallery */
    $entity_gallery = $entity_gallery_storage->load($entity_gallery->id());
    $this->assertTrue($entity_gallery->getRevisionId() > $latest_revision_id);
    $this->assertEqual($entity_gallery->label(), $initial_title);
    $this->assertFalse($entity_gallery->hasTranslation('it'));
  }

  /**
   * Creates a series of revisions for the specified entity gallery.
   *
   * @param \Drupal\entity_gallery\EntityGalleryInterface $entity_gallery
   *   The entity gallery object.
   * @param $count
   *   The number of revisions to be created.
   */
  protected function createRevisions(EntityGalleryInterface $entity_gallery, $count) {
    for ($i = 0; $i < $count; $i++) {
      $entity_gallery->title = $this->randomString();
      $entity_gallery->untranslatable_string_field->value = $this->randomString();
      $entity_gallery->setNewRevision(TRUE);
      $entity_gallery->save();
    }
  }

}
