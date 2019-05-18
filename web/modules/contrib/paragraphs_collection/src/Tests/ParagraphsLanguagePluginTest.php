<?php

namespace Drupal\paragraphs_collection\Tests;

use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\paragraphs\Tests\Experimental\ParagraphsExperimentalTestBase;

/**
 * Tests the Language plugin.
 *
 * @group paragraphs_collection
 * @requires module paragraphs
 */
class ParagraphsLanguagePluginTest extends ParagraphsExperimentalTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to be enabled.
   *
   * @var array
   */
  public static $modules = [
    'paragraphs_collection',
    'language',
  ];

  /**
   * Tests the Language plugin settings and functionality.
   */
  public function testVisibilityForLanguageSelection() {
    // Create a content type with a paragraphs field.
    $content_type = 'test_content_type';
    $paragraphs_field = 'test_paragraphs_field';
    $this->addParagraphedContentType($content_type, $paragraphs_field);
    $this->loginAsAdmin([
      'create ' . $content_type . ' content',
      'edit any ' . $content_type . ' content',
      'edit behavior plugin settings',
    ]);

    // Create a paragraphs type with a text field.
    $paragraphs_type = 'test_paragraphs_type';
    $this->addParagraphsType($paragraphs_type);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraphs_type, 'test_text_field', 'Text', 'text_long', [], []);

    // Enable the Language plugin.
    $this->drupalGet('admin/structure/paragraphs_type/' . $paragraphs_type);
    $edit = [
      'behavior_plugins[language][enabled]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Create a paragraphed content node.
    $this->drupalGet('node/add/' . $content_type);
    $this->drupalPostAjaxForm(NULL, [], $paragraphs_field . '_' . $paragraphs_type . '_add_more');
    $node_title = 'Test Node';
    $node_text = 'This is a text.';
    $edit = [
      'title[0][value]' => $node_title,
      $paragraphs_field . '[0][subform][field_test_text_field][0][value]' => $node_text,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Check that we are on the node page and the text field content is visible.
    $this->assertTitle($node_title . ' | Drupal');
    $this->assertText($node_text);

    // Check that the site has only one language and that the plugin's behavior
    // form is missing.
    $node_id = $this->drupalGetNodeByTitle($node_title)->id();
    $node_edit_path = 'node/' . $node_id . '/edit';
    $this->drupalGet($node_edit_path);
    $language_manager = \Drupal::service('language_manager');
    $this->assertFalse($language_manager->isMultilingual(), 'The site is not multilingual.');
    $this->assertNoText('Language visibility');

    // Add a second language (German) to the site.
    ConfigurableLanguage::createFromLangcode('de')->save();

    // Attempt to hide with no languages selected.
    $this->drupalGet($node_edit_path);
    $edit = [
      $paragraphs_field . '[0][behavior_plugins][language][container][visibility]' => 'hide',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Check that the plugin's behavior form is now present.
    $this->drupalGet($node_edit_path);
    $this->assertTrue($language_manager->isMultilingual(), 'The site is multilingual.');
    $this->assertText('Language visibility');

    // Hide the text field with the Language plugin for English.
    $edit = [
      $paragraphs_field . '[0][behavior_plugins][language][container][visibility]' => 'hide',
      $paragraphs_field . '[0][behavior_plugins][language][container][languages][]' => ['en'],
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Check that we are on the node page and the text field content is not
    // visible.
    $this->assertTitle($node_title . ' | Drupal');
    $this->assertNoText($node_text);

    // Hide the text field with the Language plugin for all languages but
    // German.
    $this->drupalGet($node_edit_path);
    $edit = [
      $paragraphs_field . '[0][behavior_plugins][language][container][visibility]' => 'show',
      $paragraphs_field . '[0][behavior_plugins][language][container][languages][]' => ['de'],
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Check that we are on the node page and the text field content is again
    // not visible.
    $this->assertTitle($node_title . ' | Drupal');
    // The paragraph with visibility conditions is not accessible anymore.
    $this->assertNoText($node_text);
    $this->assertNoRaw('paragraph--type--test-paragraphs-type');
    $this->assertNoRaw('<div class="field__label">test_paragraphs_field</div>');
  }

}
