<?php

namespace Drupal\entity_gallery\Tests;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\simpletest\WebTestBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_gallery\GalleryTypeCreationTrait;
use Drupal\entity_gallery\EntityGalleryCreationTrait;

/**
 * Tests multilingual support for fields.
 *
 * @group entity_gallery
 */
class EntityGalleryFieldMultilingualTest extends WebTestBase {

  use GalleryTypeCreationTrait {
    createGalleryType as drupalCreateGalleryType;
  }
  use EntityGalleryCreationTrait {
    getEntityGalleryByTitle as drupalGetEntityGalleryByTitle;
    createEntityGallery as drupalCreateEntityGallery;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_gallery', 'language', 'node');

  protected function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType([
        'type' => 'page',
        'name' => 'Basic page',
        'display_submitted' => FALSE,
      ]);
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }
    $this->accessHandler = \Drupal::entityManager()->getAccessControlHandler('node');

    // Create Basic page entity gallery type.
    $this->drupalCreateGalleryType(array('type' => 'page', 'name' => 'Basic page', 'gallery_type_bundles' => ['page' => 'page']));

    // Setup users.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'administer entity gallery types', 'access administration pages', 'create page entity galleries', 'edit own page entity galleries'));
    $this->drupalLogin($admin_user);

    // Add a new language.
    ConfigurableLanguage::createFromLangcode('it')->save();

    // Enable URL language detection and selection.
    $edit = array('language_interface[enabled][language-url]' => '1');
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

    // Set "Basic page" entity gallery type to use multilingual support.
    $edit = array(
      'language_configuration[language_alterable]' => TRUE,
    );
    $this->drupalPostForm('admin/structure/gallery-types/manage/page', $edit, t('Save gallery type'));
    $this->assertRaw(t('The gallery type %type has been updated.', array('%type' => 'Basic page')), 'Basic page entity gallery type has been updated.');

    // Make entity gallery gallery items translatable.
    $field_storage = FieldStorageConfig::loadByName('entity_gallery', 'entity_gallery_node');
    $field_storage->setTranslatable(TRUE);
    $field_storage->save();
  }

  /**
   * Tests whether field languages are correctly set through the entity gallery
   * form.
   */
  function testMultilingualEntityGalleryForm() {
    // Create "Basic page" entity gallery.
    $langcode = language_get_default_langcode('entity_gallery', 'page');
    $title_key = 'title[0][value]';
    $title_value = $this->randomMachineName(8);
    $gallery_items_key = 'entity_gallery_node[0][target_id]';
    $gallery_items_value = $this->drupalCreateNode();

    // Create entity gallery to edit.
    $edit = array();
    $edit[$title_key] = $title_value;
    $edit[$gallery_items_key] = $gallery_items_value->label();
    $this->drupalPostForm('gallery/add/page', $edit, t('Save'));

    // Check that the entity gallery exists in the database.
    $entity_gallery = $this->drupalGetEntityGalleryByTitle($edit[$title_key]);
    $this->assertTrue($entity_gallery, 'Entity gallery found in database.');
    $this->assertTrue($entity_gallery->language()->getId() == $langcode && $entity_gallery->entity_gallery_node->target_id == $gallery_items_value->id(), 'Field language correctly set.');

    // Change entity gallery language.
    $langcode = 'it';
    $this->drupalGet("gallery/{$entity_gallery->id()}/edit");
    $edit = array(
      $title_key => $this->randomMachineName(8),
      'langcode[0][value]' => $langcode,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $entity_gallery = $this->drupalGetEntityGalleryByTitle($edit[$title_key], TRUE);
    $this->assertTrue($entity_gallery, 'Entity Gallery found in database.');
    $this->assertTrue($entity_gallery->language()->getId() == $langcode && $entity_gallery->entity_gallery_node->target_id == $gallery_items_value->id(), 'Field language correctly changed.');

    // Enable content language URL detection.
    $this->container->get('language_negotiator')->saveConfiguration(LanguageInterface::TYPE_CONTENT, array(LanguageNegotiationUrl::METHOD_ID => 0));

    // Test multilingual field language fallback logic.
    $this->drupalGet("it/gallery/{$entity_gallery->id()}");
    $this->assertRaw($gallery_items_value->label(), 'Gallery item correctly displayed using Italian as requested language');

    $this->drupalGet("gallery/{$entity_gallery->id()}");
    $this->assertRaw($gallery_items_value->label(), 'Gallery item correctly displayed using English as requested language');
  }

  /*
   * Tests multilingual field display settings.
   */
  function testMultilingualDisplaySettings() {
    // Create "Basic page" entity gallery.
    $title_key = 'title[0][value]';
    $title_value = $this->randomMachineName(8);
    $gallery_items_key = 'entity_gallery_node[0][target_id]';
    $gallery_items_value = $this->drupalCreateNode();

    // Create entity gallery to edit.
    $edit = array();
    $edit[$title_key] = $title_value;
    $edit[$gallery_items_key] = $gallery_items_value->label();
    $this->drupalPostForm('gallery/add/page', $edit, t('Save'));

    // Check that the entity gallery exists in the database.
    $entity_gallery = $this->drupalGetEntityGalleryByTitle($edit[$title_key]);
    $this->assertTrue($entity_gallery, 'Entity gallery found in database.');

    // Check if entity gallery gallery item is showed.
    $this->drupalGet('gallery/' . $entity_gallery->id());
     $gallery_item = $this->xpath('//article//div[contains(concat(" ", normalize-space(@class), " "), :content-class)]/descendant::p', array(
      ':content-class' => 'field--name-entity-gallery-node',
    ));
    $this->assertEqual(current($gallery_item), $gallery_items_value->body->value, 'Entity gallery gallery item found.');
  }

}
