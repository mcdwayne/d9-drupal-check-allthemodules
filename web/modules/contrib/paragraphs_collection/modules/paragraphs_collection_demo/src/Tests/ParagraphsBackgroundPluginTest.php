<?php

namespace Drupal\paragraphs_collection_demo\Tests;

use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\paragraphs\Tests\Experimental\ParagraphsExperimentalTestBase;

/**
 * Tests the background plugin.
 *
 * @see \Drupal\paragraphs_collection\Plugin\paragraphs\Behavior\ParagraphsBackgroundPlugin
 * @group paragraphs_collection_demo
 */
class ParagraphsBackgroundPluginTest extends ParagraphsExperimentalTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to be enabled.
   *
   * @var array
   */
  public static $modules = [
    'paragraphs_collection_demo',
    'image',
    'paragraphs_collection_test',
  ];

  /**
   * Tests the background image selection plugin settings and functionality.
   */
  public function testBackgroundImageSelection() {
    // Create an article with paragraphs field.
    $this->addParagraphedContentType('article', 'paragraphs');
    $this->loginAsAdmin();

    // Add a text paragraphs type with a text field.
    $this->addParagraphsType('text_test');
    $bundle_path = 'admin/structure/paragraphs_type/text_test';
    $this->fieldUIAddExistingField($bundle_path, 'paragraphs_text');

    $bundle_path = 'admin/structure/paragraphs_type/container';
    // Add a second image field to test the correct usage of background image.
    $this->fieldUIAddNewField($bundle_path, 'second_background_image', 'Second BG image', 'image', [], []);
    // Uncheck the Alt field checkbox.
    $edit = [
      'settings[alt_field_required]' => FALSE,
    ];
    $edit_path = 'admin/structure/paragraphs_type/container/fields/paragraph.container.field_second_background_image';
    $this->drupalPostForm($edit_path, $edit, 'Save settings');

    // Create a paragraphed content.
    $this->drupalGet('node/add/article');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_container_add_more');
    // Create image files to be used and upload them.
    $background_image = $this->drupalGetTestFiles('image')[0];
    $edit = [
      'files[paragraphs_0_subform_paragraphs_background_image_0]' => $background_image->uri,
    ];
    $this->drupalPostForm(NULL, $edit, t('Upload'));
    // Add second image.
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_0_subform_paragraphs_container_paragraphs_text_test_add_more');
    $background_image = $this->drupalGetTestFiles('image')[1];
    $edit = [
      'files[paragraphs_0_subform_field_second_background_image_0]' => $background_image->uri,
    ];
    $this->drupalPostForm(NULL, $edit, t('Upload'));
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_0_subform_paragraphs_container_paragraphs_text_test_add_more');
    // Add title and body text to the node and save it.
    $edit = [
      'title[0][value]' => 'Test article',
      'paragraphs[0][subform][paragraphs_container_paragraphs][0][subform][paragraphs_text][0][value]' => "This is a non background element",
      'paragraphs[0][subform][paragraphs_background_image][0][alt]' => 'This is the alternative text',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    $save_url = $this->url;

    // Set background_image to background.
    $edit = [
      'behavior_plugins[background][settings][background_image_field]' => 'paragraphs_background_image',
    ];
    $this->drupalPostForm('admin/structure/paragraphs_type/container', $edit, 'Save');
    // Check the Background image output.
    $this->drupalGet($save_url);
    $this->assertRaw('paragraphs-behavior-background--image field field--name-paragraphs-background-image');
    $this->assertRaw('paragraphs-behavior-background--element field field--name-field-second-background-image');
    // Set background_image to second background.
    $edit = [
      'behavior_plugins[background][settings][background_image_field]' => 'field_second_background_image',
    ];
    $this->drupalPostForm('admin/structure/paragraphs_type/container', $edit, 'Save');
    // Check the Background image output.
    $this->drupalGet($save_url);
    $this->assertRaw('paragraphs-behavior-background--image field field--name-field-second-background-image');
    $this->assertRaw('paragraphs-behavior-background--element field field--name-paragraphs-background-image');

    // Save paragraph with no image uploaded.
    $this->drupalGet('node/add/article');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_container_add_more');
    $edit = [
      'title[0][value]' => 'Test article',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Add a text paragraphs type with a single image field.
    $this->addParagraphsType('image_test');
    $bundle_path = 'admin/structure/paragraphs_type/image_test';
    static::fieldUIAddNewField($bundle_path, 'image', 'Image', 'image', [], []);

    // Check that the sole available image field is selected.
    $this->drupalGet('admin/structure/paragraphs_type/image_test');
    $this->assertOption('edit-behavior-plugins-background-settings-background-image-field', '');
    $this->assertOptionSelected('edit-behavior-plugins-background-settings-background-image-field', 'field_image');

    // Check that the Background plugin can't be enabled without an image field
    // selected as the background image field.
    $edit = [
      'behavior_plugins[background][enabled]' => TRUE,
      'behavior_plugins[background][settings][background_image_field]' => '',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('The Background plugin cannot be enabled without an image field.');
  }

  /**
   * Tests the functionality when there are no image fields in a paragraph type.
   */
  public function testNoImageField() {
    // Create an article with paragraphs field.
    $this->addParagraphedContentType('article', 'paragraphs');
    $this->loginAsAdmin();

    // Add a text paragraphs type without any field.
    $this->addParagraphsType('text_test');
    $edit = [
      'behavior_plugins[background][enabled]' => TRUE,
    ];
    // Assert that the error messages are displayed.
    $this->drupalPostForm('admin/structure/paragraphs_type/text_test', $edit, t('Save'));
    $this->assertText('The Background plugin cannot be enabled without an image field.');
    $this->assertText('No image field type available. Please add at least one in the Manage fields page.');
  }

}
