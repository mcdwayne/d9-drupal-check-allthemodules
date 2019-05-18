<?php

namespace Drupal\Tests\mfd\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Tests\BrowserTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the Path admin UI.
 *
 * @group mfd
 */
class MultilingualFormUITest extends BrowserTestBase {

  /**
   * State indicating all collapsible fields are removed.
   */
  const COLLAPSIBLE_STATE_NONE = -1;

  /**
   * State indicating all collapsible fields are closed.
   */
  const COLLAPSIBLE_STATE_ALL_CLOSED = 0;

  /**
   * State indicating all collapsible fields are closed except the first one.
   */
  const COLLAPSIBLE_STATE_FIRST = 1;

  /**
   * State indicating all collapsible fields are open.
   */
  const COLLAPSIBLE_STATE_ALL_OPEN = 2;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'field_ui',
    'mfd',
    'language',
    'content_translation'
  ];


  /**
   * The added languages.
   *
   * @var array
   */
  protected $langcodes;

  /**
   * The account to be used to test translation operations.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $translator;

  protected $strictConfigSchema = FALSE;

  /**
   * The translation controller for the current entity type.
   *
   * @var \Drupal\content_translation\ContentTranslationHandlerInterface
   */
  protected $controller;

  /**
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }

    // Create test user.
    $this->drupalLogin($this->drupalCreateUser([
      'administer content',
    ]));

    $this->setupLanguages();

    $this->manager = $this->container->get('content_translation.manager');
    $this->controller = $this->manager->getTranslationHandler($this->type);

    // Rebuild the container so that the new languages are picked up by services
    // that hold a list of languages.
    $this->rebuildContainer();
  }

  /**
   * Adds additional languages.
   */
  protected function setupLanguages() {
    $this->langcodes = ['es', 'fr'];
    foreach ($this->langcodes as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    array_unshift($this->langcodes, \Drupal::languageManager()->getDefaultLanguage()->getId());
  }

  /**
   * Test the Widget and Field UI
   * - display_label (boolean)
   * - display_description (boolean)
   * - collapsible_state (options)
   * - mfd_languages (options)
   */
  public function testFieldWidgetUI() {
    $field_name = Unicode::strtolower($this->randomMachineName());

    // Create a field with settings to validate.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $this->type,
      'type' => 'multilingual_form_display',
    ]);
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => $this->bundle,
      'label' => 'Multilingual field',
      'description' => 'This is a Multilingual field description'
    ]);
    $this->field->save();


/*
    entity_get_form_display($this->type, $this->bundle, 'default')
      ->setComponent($field_name, [
        'type' => 'multilingual_form_display_widget',
        'settings' => [
          'display_label' => TRUE,
          'display_description' => TRUE,
          'collapsible_state' => self::COLLAPSIBLE_STATE_FIRST,
          'mfd_languages' => ['es','fr']
        ]
      ])
      ->save();

    'display_label' => TRUE,
        'display_description' => TRUE,
        'collapsible_state' => COLLAPSIBLE_STATE_FIRST,
        'display_translate_table' => FALSE,

    entity_get_display($this->type, $this->bundle, 'full')
      ->setComponent($field_name, [
        'type' => 'multilingual_form_display',
        'label' => 'hidden',
        'settings' => [
          'display_label' => 'http://example.com',
          'display_description' => 'Enter the text for this link',
        ],
      ])
      ->save();
*/

  }

  /**
   * Test the Form Display UI (node/add & node/edit)
   * - each language
   * - mfd field in position (multiple)
   * - display_label
   * - display_description
   * - field position
   * - field summary
   */
  public function testFormDisplayUI() {
    $field_name = Unicode::strtolower($this->randomMachineName());

    // Create a field with settings to validate.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $this->type,
      'type' => 'multilingual_form_display',
    ]);
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => $this->bundle,
      'label' => 'Multilingual field',
      'description' => 'This is a Multilingual field description'
    ]);
    $this->field->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertSession()->fieldExists('title');
//    foreach($this->langcodes as $langcode) {
//      if ($langcode !== \Drupal::languageManager()->getDefaultLanguage()->getId()) {
//        $this->assertSession()->fieldExists('edit-title-0-value');
//      } else {
//        $this->assertSession()->fieldExists("edit-title-{$langcode}-0-value");
//      }
//    }
    //    $this->assertRaw('value="untitled"');

  }

  /**
   * Test the Form Display UI
   * - display_label
   * - display_description
   * - collapsible_state
   * - display_translate_table
   */
//  public function testFormatterUI() {
//
//  }

  /**
   * Test the Form Display UI
   * - field position
   * - field summary
   */
//  public function testViewDisplayUI() {
//
//  }

  /**
   * Test the Entity Rendering UI
   * - field position
   * - field summary
   */
//  public function testEntityRender() {

    //    $entity->addTranslation($langcode, $values);

    //    $published = $metadata_source_translation->isPublished();
    //    $this->assertEqual($published, $metadata_target_translation->isPublished(), 'Metadata published field has the same value for both translations.');

    //    $metadata_target_translation->setPublished(TRUE);
    //    $this->assertNotEqual($metadata_source_translation->isPublished(), $metadata_target_translation->isPublished(), 'Metadata published field correctly different on both tr
//  }



}
