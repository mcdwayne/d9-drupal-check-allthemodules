<?php

namespace Drupal\paragraphs_collection_demo\Tests;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

use Drupal\paragraphs\Tests\Experimental\ParagraphsExperimentalTestBase;

/**
 * Tests Slider plugin.
 *
 * @see \Drupal\paragraphs_collection_demo\Plugin\paragraphs\Behavior\ParagraphsSliderPlugin
 * @group paragraphs_collection_demo
 * @requires module slick
 */
class ParagraphsSliderPluginTest extends ParagraphsExperimentalTestBase {

  /**
   * Modules to be enabled.
   *
   * @var array
   */
  public static $modules = [
    'slick',
    'paragraphs_collection',
    'paragraphs_collection_demo',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loginAsAdmin([
      'administer modules',
      'edit behavior plugin settings'
    ]);
    $this->addParagraphedContentType('paragraphed_test');
    $this->addParagraphsType('slide_content');
  }

  /**
   * Tests creating slider content.
   */
  public function testCreatingSliderContent() {
    $this->fieldUIAddExistingField('admin/structure/paragraphs_type/slide_content', 'paragraphs_text', 'slide_content');
    $this->assertText('Saved slide_content configuration');

    // Add new content.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_slider_add_more');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_0_subform_field_slides_slide_content_add_more');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_0_subform_field_slides_text_add_more');
    $edit = [
      'title[0][value]' => 'Slider',
      'field_paragraphs[0][subform][field_slides][0][subform][paragraphs_text][0][value]' => 'First slide.',
      'field_paragraphs[0][subform][field_slides][1][subform][paragraphs_text][0][value]' => 'Second slide.',
      'field_paragraphs[0][behavior_plugins][slider][slick_slider]' => 'default'
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('paragraphed_test Slider has been created.');

    $this->drupalGet('node/1');
    $xpath = '//div[@class = "paragraph paragraph--type--slider paragraph--view-mode--default"]//div[contains(@class, "slick")]';
    $this->assertFieldByXPath($xpath, NULL, "Slick class found");

    $this->drupalGet('admin/structure/paragraphs_type/slider/fields');
    $this->drupalPostForm('admin/structure/paragraphs_type/slider/fields/paragraph.slider.field_slides/delete', [], t('Delete'));
    $this->assertText('The field Slides has been deleted from the Slider content type.');

    $node = $this->getNodeByTitle('Slider');
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);

    $this->drupalGet('admin/structure/paragraphs_type/slider');
    $this->assertResponse(200);
    $this->assertText('There are no fields available with the cardinality greater than one. Please add at least one in the Manage fields page.');
    $this->drupalPostForm(NULL, ['behavior_plugins[slider][enabled]' => TRUE], t('Save'));
    $this->assertText('The Slider plugin cannot be enabled if there is no field to be mapped.');
  }

  /**
   * Tests configuration form for slider plugin.
   */
  public function testConfigurationForm() {

    //Add a new field
    $this->drupalGet('admin/structure/paragraphs_type/slider/fields/add-field');
    $edit = [
        'new_storage_type' => 'text_long',
        'label' => 'Text',
        'field_name' => 'paragraphs_text',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and continue'));
    $edit = [
      'cardinality' => '-1',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    $this->assertText('Updated field Text field settings');
    $this->drupalPostForm(NULL, NULL, t('Save settings'));
    $this->assertText('Saved Text configuration');

    //Choose the field to be used as slider items
    $this->drupalGet('admin/structure/paragraphs_type/slider');
    $this->assertText('Slick Optionsets');
    $this->assertText('Enable the Slick UI from the module list to create more options.');
    $edit = [
      'behavior_plugins[slider][settings][field_name]' => 'field_paragraphs_text',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('Saved the Slider Paragraphs type.');

    // Add slider content.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_slider_add_more');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_text_add_more');
    $edit = [
    'title[0][value]' => 'SldierDemo',
    'field_paragraphs[0][subform][field_paragraphs_text][0][value]' => 'First slide.',
    'field_paragraphs[1][subform][paragraphs_text][0][value]' => 'Second slide',
    'field_paragraphs[0][behavior_plugins][slider][slick_slider]' => 'default',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('paragraphed_test SldierDemo has been created.');
  }

  /**
   * Checks getOptionsetDescription method.
   */
  public function testOptionSetDescription() {
    $this->loginAsAdmin();
    $this->drupalGet('admin/structure/paragraphs_type/slider');
    $this->assertText('Select none, to show all.');
    $this->assertNoText('Enable the Slick UI from the module list to create more options.');
    $this->loginAsAdmin([
      'administer modules',
    ]);
    $this->drupalGet('admin/structure/paragraphs_type/slider');
    $this->assertText('Enable the Slick UI from the module list to create more options.');
    \Drupal::service('module_installer')->install(['slick_ui']);
    $this->loginAsAdmin([
      'administer modules',
      'administer slick',
    ]);
    \Drupal::service('module_installer')->install(['slick_ui']);
    $this->drupalGet('admin/structure/paragraphs_type/slider');
    $this->assertText('To have more options, go to the Slick UI config page and add items there.');
  }

  /**
   * Tests configuration of slider plugin.
   */
  public function testSliderSettingsSummary() {
    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'edit behavior plugin settings',
    ]);

    // Add a slide_content paragraph type.
    $this->fieldUIAddExistingField('admin/structure/paragraphs_type/slide_content', 'paragraphs_text', 'slide_content');
    $this->setParagraphsWidgetMode('paragraphed_test', 'field_paragraphs', 'closed');

    // Node edit: add three slides paragraph type.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_slider_add_more');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_0_subform_field_slides_slide_content_add_more');
    $this->drupalPostAjaxForm(NULL, NULL, 'field_paragraphs_0_subform_field_slides_text_add_more');
    $edit = [
      'title[0][value]' => 'Slider plugin summary',
      'field_paragraphs[0][subform][field_slides][0][subform][paragraphs_text][0][value]' => 'First slide',
      'field_paragraphs[0][subform][field_slides][1][subform][paragraphs_text][0][value]' => 'Second slide',
      'field_paragraphs[0][behavior_plugins][slider][slick_slider]' => 'default',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('paragraphed_test Slider plugin summary has been created.');

    // Assert that the summary includes the text of the behavior plugins.
    $this->clickLink('Edit');
    $this->assertRaw('<span class="summary-content">First slide</span>, <span class="summary-content">Second slide</span></div><div class="paragraphs-plugin-wrapper"><span class="summary-plugin"><span class="summary-plugin-label">Slider settings</span>Default');
    $this->assertFieldByXPath('//*[@id="edit-field-paragraphs-0-top-icons"]/span[@class="paragraphs-badge" and @title="2 children"]');
  }

}
