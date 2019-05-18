<?php

namespace Drupal\entity_gallery\Tests\Views;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\views\Views;

/**
 * Tests an entity gallery bulk form.
 *
 * @group entity_gallery
 * @see \Drupal\entity_gallery\Plugin\views\field\BulkForm
 */
class BulkFormTest extends EntityGalleryTestBase {

  /**
   * Modules to be enabled.
   *
   * @var array
   */
  public static $modules = array('entity_gallery_test_views', 'language');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_entity_gallery_bulk_form');

  /**
   * The test entity galleries.
   *
   * @var \Drupal\entity_gallery\EntityGalleryInterface[]
   */
  protected $entity_galleries;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    ConfigurableLanguage::createFromLangcode('en-gb')->save();
    ConfigurableLanguage::createFromLangcode('it')->save();

    // Create some test entity galleries.
    $this->entity_galleries = [];
    $langcodes = ['en', 'en-gb', 'it'];
    for ($i = 1; $i <= 5; $i++) {
      $langcode = $langcodes[($i - 1) % 3];
      $values = [
        'title' => $this->randomMachineName() . ' [' . $i . ':' . $langcode . ']',
        'langcode' => $langcode,
      ];
      $entity_gallery = $this->drupalCreateEntityGallery($values);
      $this->pass(SafeMarkup::format('Entity gallery %title created with language %langcode.', ['%title' => $entity_gallery->label(), '%langcode' => $entity_gallery->language()->getId()]));
      $this->entity_galleries[] = $entity_gallery;
    }

    // Create translations for all languages for some entity galleries.
    for ($i = 0; $i < 2; $i++) {
      $entity_gallery = $this->entity_galleries[$i];
      foreach ($langcodes as $langcode) {
        if (!$entity_gallery->hasTranslation($langcode)) {
          $title = $this->randomMachineName() . ' [' . $entity_gallery->id() . ':' . $langcode . ']';
          $translation = $entity_gallery->addTranslation($langcode, ['title' => $title]);
          $this->pass(SafeMarkup::format('Translation %title created with language %langcode.', ['%title' => $translation->label(), '%langcode' => $translation->language()->getId()]));
        }
      }
      $entity_gallery->save();
    }

    // Create an entity gallery with only one translation.
    $entity_gallery = $this->entity_galleries[2];
    $langcode = 'en';
    $title = $this->randomMachineName() . ' [' . $entity_gallery->id() . ':' . $langcode . ']';
    $translation = $entity_gallery->addTranslation($langcode, ['title' => $title]);
    $this->pass(SafeMarkup::format('Translation %title created with language %langcode.', ['%title' => $translation->label(), '%langcode' => $translation->language()->getId()]));
    $entity_gallery->save();

    // Check that all created translations are selected by the test view.
    $view = Views::getView('test_entity_gallery_bulk_form');
    $view->execute();
    $this->assertEqual(count($view->result), 10, 'All created translations are selected.');

    // Check the operations are accessible to the logged in user.
    $this->drupalLogin($this->drupalCreateUser(array('administer entity galleries', 'access entity gallery overview', 'bypass entity gallery access')));
    $this->drupalGet('test-entity-gallery-bulk-form');
    $elements = $this->xpath('//select[@id="edit-action"]//option');
    $this->assertIdentical(count($elements), 4, 'All entity gallery operations are found.');
  }

  /**
   * Tests the entity gallery bulk form.
   */
  public function testBulkForm() {
    // Unpublish an entity gallery using the bulk form.
    $entity_gallery = reset($this->entity_galleries);
    $this->assertTrue($entity_gallery->isPublished(), 'Entity gallery is initially published');
    $this->assertTrue($entity_gallery->getTranslation('en-gb')->isPublished(), 'Entity gallery translation is published');
    $this->assertTrue($entity_gallery->getTranslation('it')->isPublished(), 'Entity gallery translation is published');
    $edit = array(
      'entity_gallery_bulk_form[0]' => TRUE,
      'action' => 'entity_gallery_unpublish_action',
    );
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $entity_gallery = $this->loadEntityGallery($entity_gallery->id());
    $this->assertFalse($entity_gallery->isPublished(), 'Entity gallery has been unpublished');
    $this->assertTrue($entity_gallery->getTranslation('en-gb')->isPublished(), 'Entity gallery translation has not been unpublished');
    $this->assertTrue($entity_gallery->getTranslation('it')->isPublished(), 'Entity gallery translation has not been unpublished');

    // Publish action.
    $edit = array(
      'entity_gallery_bulk_form[0]' => TRUE,
      'action' => 'entity_gallery_publish_action',
    );
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $entity_gallery = $this->loadEntityGallery($entity_gallery->id());
    $this->assertTrue($entity_gallery->isPublished(), 'Entity gallery has been published again');

    // Select a bunch of translated and untranslated entity galleries and check
    // that operations are always applied to individual translations.
    $edit = array(
      // Original and all translations.
      'entity_gallery_bulk_form[0]' => TRUE, // Entity gallery 1, English, original.
      'entity_gallery_bulk_form[1]' => TRUE, // Entity gallery 1, British English.
      'entity_gallery_bulk_form[2]' => TRUE, // Entity gallery 1, Italian.
      // Original and only one translation.
      'entity_gallery_bulk_form[3]' => TRUE, // Entity gallery 2, English.
      'entity_gallery_bulk_form[4]' => TRUE, // Entity gallery 2, British English, original.
      'entity_gallery_bulk_form[5]' => FALSE, // Entity gallery 2, Italian.
      // Only a single translation.
      'entity_gallery_bulk_form[6]' => TRUE, // Entity gallery 3, English.
      'entity_gallery_bulk_form[7]' => FALSE, // Entity gallery 3, Italian, original.
      // Only a single untranslated entity gallery.
      'entity_gallery_bulk_form[8]' => TRUE, // Entity gallery 4, English, untranslated.
      'entity_gallery_bulk_form[9]' => FALSE, // Entity gallery 5, British English, untranslated.
      'action' => 'entity_gallery_unpublish_action',
    );
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $entity_gallery = $this->loadEntityGallery(1);
    $this->assertFalse($entity_gallery->getTranslation('en')->isPublished(), '1: English translation has been unpublished');
    $this->assertFalse($entity_gallery->getTranslation('en-gb')->isPublished(), '1: British English translation has been unpublished');
    $this->assertFalse($entity_gallery->getTranslation('it')->isPublished(), '1: Italian translation has been unpublished');
    $entity_gallery = $this->loadEntityGallery(2);
    $this->assertFalse($entity_gallery->getTranslation('en')->isPublished(), '2: English translation has been unpublished');
    $this->assertFalse($entity_gallery->getTranslation('en-gb')->isPublished(), '2: British English translation has been unpublished');
    $this->assertTrue($entity_gallery->getTranslation('it')->isPublished(), '2: Italian translation has not been unpublished');
    $entity_gallery = $this->loadEntityGallery(3);
    $this->assertFalse($entity_gallery->getTranslation('en')->isPublished(), '3: English translation has been unpublished');
    $this->assertTrue($entity_gallery->getTranslation('it')->isPublished(), '3: Italian translation has not been unpublished');
    $entity_gallery = $this->loadEntityGallery(4);
    $this->assertFalse($entity_gallery->isPublished(), '4: Entity gallery has been unpublished');
    $entity_gallery = $this->loadEntityGallery(5);
    $this->assertTrue($entity_gallery->isPublished(), '5: Entity gallery has not been unpublished');
  }

  /**
   * Test multiple deletion.
   */
  public function testBulkDeletion() {
    // Select a bunch of translated and untranslated entity galleries and check
    // that entity galleries and individual translations are properly deleted.
    $edit = array(
      // Original and all translations.
      'entity_gallery_bulk_form[0]' => TRUE, // Entity gallery 1, English, original.
      'entity_gallery_bulk_form[1]' => TRUE, // Entity gallery 1, British English.
      'entity_gallery_bulk_form[2]' => TRUE, // Entity gallery 1, Italian.
      // Original and only one translation.
      'entity_gallery_bulk_form[3]' => TRUE, // Entity gallery 2, English.
      'entity_gallery_bulk_form[4]' => TRUE, // Entity gallery 2, British English, original.
      'entity_gallery_bulk_form[5]' => FALSE, // Entity gallery 2, Italian.
      // Only a single translation.
      'entity_gallery_bulk_form[6]' => TRUE, // Entity gallery 3, English.
      'entity_gallery_bulk_form[7]' => FALSE, // Entity gallery 3, Italian, original.
      // Only a single untranslated entity gallery.
      'entity_gallery_bulk_form[8]' => TRUE, // Entity gallery 4, English, untranslated.
      'entity_gallery_bulk_form[9]' => FALSE, // Entity gallery 5, British English, untranslated.
      'action' => 'entity_gallery_delete_action',
    );
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));

    $label = $this->loadEntityGallery(1)->label();
    $this->assertText("$label (Original translation) - The following content translations will be deleted:");
    $label = $this->loadEntityGallery(2)->label();
    $this->assertText("$label (Original translation) - The following content translations will be deleted:");
    $label = $this->loadEntityGallery(3)->getTranslation('en')->label();
    $this->assertText($label);
    $this->assertNoText("$label (Original translation) - The following content translations will be deleted:");
    $label = $this->loadEntityGallery(4)->label();
    $this->assertText($label);
    $this->assertNoText("$label (Original translation) - The following content translations will be deleted:");

    $this->drupalPostForm(NULL, array(), t('Delete'));

    $entity_gallery = $this->loadEntityGallery(1);
    $this->assertNull($entity_gallery, '1: Entity gallery has been deleted');
    $entity_gallery = $this->loadEntityGallery(2);
    $this->assertNull($entity_gallery, '2: Entity gallery has been deleted');
    $entity_gallery = $this->loadEntityGallery(3);
    $result = count($entity_gallery->getTranslationLanguages()) && $entity_gallery->language()->getId() == 'it';
    $this->assertTrue($result, '3: English translation has been deleted');
    $entity_gallery = $this->loadEntityGallery(4);
    $this->assertNull($entity_gallery, '4: Entity gallery has been deleted');
    $entity_gallery = $this->loadEntityGallery(5);
    $this->assertTrue($entity_gallery, '5: Entity gallery has not been deleted');

    $this->assertText('Deleted 8 posts.');
  }

  /**
   * Load the specified entity gallery from the storage.
   *
   * @param int $id
   *   The entity gallery identifier.
   *
   * @return \Drupal\entity_gallery\EntityGalleryInterface
   *   The loaded entity gallery.
   */
  protected function loadEntityGallery($id) {
    /** @var \Drupal\entity_gallery\EntityGalleryStorage $storage */
    $storage = $this->container->get('entity.manager')->getStorage('entity_gallery');
    $storage->resetCache([$id]);
    return $storage->load($id);
  }

}
