<?php

namespace Drupal\paragraphs_collection_demo\Tests;

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Tests\Experimental\ParagraphsExperimentalTestBase;

/**
 * Test the demo for Paragraphs Collection.
 *
 * @group paragraphs_collection_demo
 */
class ParagraphsCollectionDemoTest extends ParagraphsExperimentalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'paragraphs_collection_demo',
  ];

  /**
   * Checks that generic container is created with all plugins enabled.
   */
  public function testConfiguration() {
    $this->loginAsAdmin([
      'administer content types',
      'access administration pages',
      'access content overview',
    ]);

    // Check for pre-configured paragraph type.
    $this->drupalGet('admin/structure/paragraphs_type/container');
    $this->assertText('Container');
    $this->assertFieldChecked('edit-behavior-plugins-style-enabled');
    $this->assertFieldChecked('edit-behavior-plugins-style-settings-groups-general-group');
    $this->assertNoFieldChecked('edit-behavior-plugins-style-settings-groups-slideshow-group');
    $options = $this->xpath('//input[contains(@id, :id)]', [':id' => 'edit-behavior-plugins-style-settings-groups']);
    $this->assertEqual(2, count($options));
    // @todo When other plugins are available, add assertion.

    $this->drupalGet('admin/structure/paragraphs_type/text');
    $this->assertFieldChecked('edit-behavior-plugins-language-enabled');

    // Check that demo content has paragraph with enabled plugins.
    $this->drupalGet('admin/content');
    $this->clickLink('Paragraphs Collection Demo Article!');
    $this->assertText('Paragraphs');

    $this->drupalGet('');
    $this->assertResponse(200);
    $this->assertLink('Paragraphs Collection Demo Article!');
    $this->assertText('This is content from the library. We can reuse it multiple times without duplicating it.');
  }

  /**
   * Tests the demo styles for the style plugin.
   */
  public function testDemoStyles() {
    $this->loginAsAdmin([
      'administer content types',
      'access administration pages',
      'access content overview',
      'administer site configuration',
      'create paragraphed_content_demo content',
      'edit any paragraphed_content_demo content',
      'delete any paragraphed_content_demo content',
    ]);
    // Create text paragraph.
    $text_paragraph = Paragraph::create([
      'type' => 'text',
      'paragraphs_text' => [
        'value' => '<p>Introduces a new set of styles for the style plugin.</p>',
        'format' => 'basic_html',
      ],
    ]);
    $text_paragraph->save();

    // Create container that contains the text paragraph.
    $paragraph = Paragraph::create([
      'title' => 'Styled paragraph',
      'type' => 'container',
      'paragraphs_container_paragraphs' => [$text_paragraph],
    ]);

    // Add demo content with one paragraph.
    $node = Node::create([
      'type' => 'paragraphed_content_demo',
      'title' => 'Style plugin test',
      'langcode' => 'en',
      'uid' => '0',
      'status' => 1,
      'field_paragraphs_demo' => [$paragraph],
    ]);
    $node->save();

    // Use green style for this container.
    $paragraph->setBehaviorSettings('style', ['styles' => ['general_group' => 'paragraphs-green']]);
    $paragraph->save();

    // Check the applied style on the paragraph.
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw('paragraphs-behavior-background');
    $this->assertRaw('paragraphs-behavior-style--paragraphs-green');
    $this->assertRaw('paragraph--type--container');
    $this->assertRaw('paragraph--view-mode--default');

    // Use blue style for the container.
    $paragraph->setBehaviorSettings('style', ['styles' => ['general_group' => 'paragraphs-blue']]);
    $paragraph->save();

    // Check that the blue style is applied on the paragraph.
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw('paragraphs-behavior-background');
    $this->assertRaw('paragraphs-behavior-style--paragraphs-blue');
    $this->assertRaw('paragraph--type--container');
    $this->assertRaw('paragraph--view-mode--default');
  }

  /**
   * Tests the "Paragraphs Collection Demo Article!" demo node.
   */
  public function testDemoNode() {
    $this->loginAsAdmin([
      'edit any paragraphed_content_demo content',
      'administer lockable paragraph',
      'use text format basic_html',
    ]);

    // Edit and save "Paragraphs Collection Demo Article!" to test validity.
    $this->drupalGet('node/1/edit');
    $this->assertText('Edit Paragraphed Content Demo Paragraphs Collection Demo Article!');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_demo_0_edit');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_demo_0_subform_paragraphs_container_paragraphs_0_duplicate');
    $this->drupalPostForm(NULL, [], 'Save');
    $this->assertText('Paragraphed Content Demo Paragraphs Collection Demo Article! has been updated.');
  }

  /**
   * Tests that demo node is using experimental widget.
   */
  public function testUsingExperimentalWidget() {
    $this->loginAsAdmin(['edit any paragraphed_content_demo content']);
    $this->drupalGet('admin/structure/types/manage/paragraphed_content_demo/form-display');
    $this->assertOptionSelected('edit-fields-field-paragraphs-demo-type', 'paragraphs', 'Using experimental widget.');
  }

  /**
   * Tests paragraph types.
   */
  public function testParagraphTypes() {
    $this->addParagraphedContentType('paragraphed_test');
    $this->loginAsAdmin([
        'create paragraphed_test content',
        'edit any paragraphed_test content',
        'administer paragraphs library',
    ]);
    $this->drupalGet('/node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_image_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'field_paragraphs_image_text_add_more');
    $image = current($this->drupalGetTestFiles('image'));
    $edit = [
      'title[0][value]' => 'Paragraph types example',
      'files[field_paragraphs_0_subform_paragraphs_image_0]' => drupal_realpath($image->uri),
      'field_paragraphs[1][subform][paragraphs_text][0][value]' => 'Text test with image',
      'files[field_paragraphs_1_subform_paragraphs_image_0]' => drupal_realpath($image->uri),
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Asserts the text and image type.
    $this->assertText('Text test with image');
    $this->assertRaw($image->filename);
  }

}
