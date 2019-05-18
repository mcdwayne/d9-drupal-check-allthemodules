<?php

namespace Drupal\paragraphs_collection_demo\Tests;

use Drupal\paragraphs\Tests\Experimental\ParagraphsExperimentalTestBase;

/**
 * Tests Accordion plugin.
 *
 * @see \Drupal\paragraphs_collection_demo\Plugin\paragraphs\Behavior\ParagraphsAccordionPlugin
 * @group paragraphs_collection_demo
 */
class ParagraphsAccordionPluginTest extends ParagraphsExperimentalTestBase {

  /**
   * Modules to be enabled.
   *
   * @var array
   */
  public static $modules = [
    'paragraphs_collection_demo',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loginAsAdmin([
      'administer modules',
      'edit behavior plugin settings',
    ]);
    $this->addParagraphedContentType('paragraphed_accordion_test');
    $this->addParagraphsType('accordion_content');
    static::fieldUIAddNewField('admin/structure/paragraphs_type/accordion_content', 'accordion_content', 'accordion_title', 'text_long', ['cardinality' => 'number', 'cardinality_number' => '1'], []);
  }

  /**
   * Test creating accordion content.
   */
  public function testCreatingAccordionContent() {
    $this->drupalGet('node/add/paragraphed_accordion_test');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_accordion_add_more');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_0_subform_paragraphs_accordion_paragraphs_accordion_content_add_more');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_0_subform_paragraphs_accordion_paragraphs_accordion_content_add_more');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_0_subform_paragraphs_accordion_paragraphs_accordion_content_add_more');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_0_subform_paragraphs_accordion_paragraphs_accordion_content_add_more');
    $edit = [
      'title[0][value]' => 'Accordion',
      'field_paragraphs[0][subform][paragraphs_accordion_paragraphs][0][subform][field_accordion_content][0][value]' => 'Title',
      'field_paragraphs[0][subform][paragraphs_accordion_paragraphs][1][subform][field_accordion_content][0][value]' => 'Body text.',
      'field_paragraphs[0][subform][paragraphs_accordion_paragraphs][2][subform][field_accordion_content][0][value]' => 'Second title',
      'field_paragraphs[0][subform][paragraphs_accordion_paragraphs][3][subform][field_accordion_content][0][value]' => 'Second Body text.',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    $this->drupalGet('node/1');
    // Ensure expected markup for an accordion.
    $elements = $this->xpath('//div[contains(@class, :accordion-class)]/div[contains(@class, :items-class)]', [
      ':accordion-class' => 'accordion',
      ':items-class' => 'field__items',
    ]);
    $this->assertTrue(!empty($elements), 'The proper accordion markup was found.');

    $this->drupalPostForm('admin/structure/paragraphs_type/accordion/fields/paragraph.accordion.paragraphs_accordion_paragraphs/delete', [], t('Delete'));
    $this->assertText('The field Accordion has been deleted from the Accordion content type.');

    $node = $this->getNodeByTitle('Accordion');
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);

    $this->drupalGet('admin/structure/paragraphs_type/accordion');
    $this->assertResponse(200);
    $this->drupalPostForm(NULL, ['behavior_plugins[accordion][enabled]' => TRUE], t('Save'));
    $this->assertText('The Accordion plugin cannot be enabled if the accordion field is missing.');
  }

  /**
   * Test accordion plugin configuration form.
   */
  public function testConfigurationForm() {
    $this->drupalGet('admin/structure/paragraphs_type/accordion_content');
    $this->assertText('There are no fields available with the cardinality greater than one. Please add at least one in the Manage fields page.');

    $this->drupalGet('admin/structure/paragraphs_type/accordion');
    $this->assertText('Accordion effect for paragraphs.');
    $this->assertOptionSelected('edit-behavior-plugins-accordion-settings-paragraph-accordion-field', 'paragraphs_accordion_paragraphs');
    $this->assertText('Choose a field to be used as the accordion container.');
  }

}
