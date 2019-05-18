<?php

namespace Drupal\paragraphs_collection\Tests;

use Drupal\paragraphs\Tests\Experimental\ParagraphsExperimentalTestBase;

/**
 * Tests the grid layout plugin.
 *
 * @see \Drupal\paragraphs_collection\Plugin\paragraphs\Behavior\ParagraphsGridLayoutPlugin
 * @group paragraphs_collection
 */
class ParagraphsGridLayoutPluginTest extends ParagraphsExperimentalTestBase {

  /**
   * Modules to be enabled.
   */
  public static $modules = [
    'paragraphs_collection',
    'paragraphs_collection_test',
  ];

  /**
   * Tests the grid layout plugin settings and functionality.
   */
  public function testGridLayoutPlugin() {
    $this->loginAsAdmin(['edit behavior plugin settings']);

    // Paragraph types add form.
    $this->drupalGet('admin/structure/paragraphs_type/add');
    $this->assertText('Grid layout');

    // Paragraph type edit form.
    $this->drupalGet('admin/structure/paragraphs_type/grid');
    $this->assertFieldChecked('edit-behavior-plugins-grid-layout-enabled');
    $this->assertText('Grid field');
    $this->assertOptionSelected('edit-behavior-plugins-grid-layout-settings-paragraph-reference-field', 'paragraphs_container_paragraphs');
    $this->assertText('Grid layouts');
    $this->assertText('Two columns');

    // Test that entity reference field is also an option if cardinality is
    // greater than 1.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/grid', 'user_reference', 'User', 'entity_reference', ['settings[target_type]' => 'user'], []);
    $this->drupalGet('admin/structure/paragraphs_type/grid');
    $this->assertNoOption('edit-behavior-plugins-grid-layout-settings-paragraph-reference-field', 'field_user_reference');
    $this->drupalGet('admin/structure/paragraphs_type/grid/fields/paragraph.grid.field_user_reference/storage');
    $edit = [
      'cardinality' => '-1',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    $this->drupalGet('admin/structure/paragraphs_type/grid');
    $this->assertOption('edit-behavior-plugins-grid-layout-settings-paragraph-reference-field', 'field_user_reference');
    $this->drupalPostForm('admin/structure/paragraphs_type/grid/fields/paragraph.grid.field_user_reference/delete', [], t('Delete'));
    $this->assertText('The field User has been deleted from the Grid content type.');

    // Node creation.
    $this->addParagraphedContentType('paragraphed_test', 'paragraphs_container');
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_container_grid_add_more');

    // Check that the grid layout options are sorted alphabetically.
    $layours = $this->xpath('//select[contains(@id, :id)]', [':id' => 'edit-paragraphs-container-0-behavior-plugins-grid-layout-layout']);
    $this->assertEqual('- None -', $layours[0]->option[0]);
    $this->assertEqual('Three columns', $layours[0]->option[1]);
    $this->assertEqual('Two columns', $layours[0]->option[2]);

    // Create a grid of paragraphs.
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_container_0_subform_paragraphs_container_paragraphs_container_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_container_0_subform_paragraphs_container_paragraphs_container_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_container_0_subform_paragraphs_container_paragraphs_container_add_more');
    $edit = [
      'title[0][value]' => 'Grid',
      'paragraphs_container[0][behavior_plugins][grid_layout][layout_wrapper][layout]' => 'paragraphs_collection_test_two_column',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('paragraphed_test Grid has been created.');
    $this->assertRaw('paragraphs_collection_test/css/grid-layout.css');

    $this->drupalGet('node/1');
    // We ship with the grid container label hidden, so we don't have the
    // field__items wrapper.
    $grid_columns[] = '//div[contains(@class, "paragraphs-behavior-grid-layout-row")]/div[1][contains(@class, "paragraphs-behavior-grid-layout-col-8")]';
    $grid_columns[] = '//div[contains(@class, "paragraphs-behavior-grid-layout-row")]/div[2][contains(@class, "paragraphs-behavior-grid-layout-col-4")]';
    $grid_columns[] = '//div[contains(@class, "paragraphs-behavior-grid-layout-row")]/div[3][contains(@class, "paragraphs-behavior-grid-layout-col-8")]';
    foreach ($grid_columns as $key => $column) {
      $this->assertFieldByXPath($column, NULL, "Grid structure found for column {$key}");
    }

    $this->drupalPostForm('admin/structure/paragraphs_type/grid/fields/paragraph.grid.paragraphs_container_paragraphs/delete', [], t('Delete'));
    $this->assertText('The field Paragraphs has been deleted from the Grid content type.');

    $node = $this->getNodeByTitle('Grid');
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);

    $this->drupalGet('admin/structure/paragraphs_type/grid');
    $this->assertResponse(200);
    $this->assertText('No paragraph reference field type available. Please add at least one in the Manage fields page.');
    $this->drupalPostForm(NULL, ['behavior_plugins[grid_layout][enabled]' => TRUE], t('Save'));
    $this->assertText('The grid layout plugin cannot be enabled if the paragraph reference field is missing.');
  }

  /**
   * Tests Grid plugin summary for paragraphs closed mode.
   */
  public function testGridSettingsSummary() {
    $this->addParagraphedContentType('paragraphed_test', 'paragraphs_container');
    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'edit behavior plugin settings',
    ]);

    // Add a text paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    $this->fieldUIAddExistingField('admin/structure/paragraphs_type/' . $paragraph_type, 'paragraphs_text');
    $this->setParagraphsWidgetMode('paragraphed_test', 'paragraphs_container', 'closed');

    // Node edit: add three text into the grid paragraph type, set grid layout.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_container_grid_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_container_0_subform_paragraphs_container_paragraphs_text_paragraph_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_container_0_subform_paragraphs_container_paragraphs_text_paragraph_add_more');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_container_0_subform_paragraphs_container_paragraphs_text_paragraph_add_more');
    $edit = [
      'title[0][value]' => 'Grid plugin summary',
      'paragraphs_container[0][subform][paragraphs_container_paragraphs][0][subform][paragraphs_text][0][value]' => 'Text 1',
      'paragraphs_container[0][subform][paragraphs_container_paragraphs][1][subform][paragraphs_text][0][value]' => 'Text 2',
      'paragraphs_container[0][subform][paragraphs_container_paragraphs][2][subform][paragraphs_text][0][value]' => 'Text 3',
      'paragraphs_container[0][behavior_plugins][grid_layout][layout_wrapper][layout]' => 'paragraphs_collection_test_two_column',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('paragraphed_test Grid plugin summary has been created.');

    // Assert that the summary includes the text of the behavior plugins.
    $this->clickLink('Edit');
    $this->assertRaw('<span class="summary-content">Text 1</span>, <span class="summary-content">Text 2</span>, <span class="summary-content">Text 3</span></div><div class="paragraphs-plugin-wrapper"><span class="summary-plugin"><span class="summary-plugin-label">Layout</span>Two columns');
    $this->assertFieldByXPath('//*[@id="edit-paragraphs-container-0-top-icons"]/span[@class="paragraphs-badge" and @title="3 children"]');
  }

  /**
   * Tests creation of empty grid.
   */
  public function testEmptyGridPlugin() {
    $this->addParagraphedContentType('paragraphed_test', 'paragraphs_container');
    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'edit behavior plugin settings',
    ]);

    // Set an empty grid layout in a node.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_container_grid_add_more');
    $edit = [
      'title[0][value]' => 'Grid plugin summary',
      'paragraphs_container[0][behavior_plugins][grid_layout][layout_wrapper][layout]' => 'paragraphs_collection_test_two_column',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertNoErrorsLogged();

    // Ensure that not selecting layouts will not save any into configuration.
    $edit = [
      'behavior_plugins[grid_layout][enabled]' => TRUE,
      'behavior_plugins[grid_layout][settings][paragraph_reference_field]' => 'paragraphs_container_paragraphs',
      'behavior_plugins[grid_layout][settings][available_grid_layouts][paragraphs_collection_test_two_column]' => FALSE,
      'behavior_plugins[grid_layout][settings][available_grid_layouts][paragraphs_collection_test_three_column]' => FALSE,
    ];
    $this->drupalPostForm('admin/structure/paragraphs_type/grid', $edit, 'Save');
    $saved_grid_layouts = \Drupal::config('paragraphs.paragraphs_type.grid')->get('behavior_plugins.grid_layout.available_grid_layouts');
    $this->assertEqual($saved_grid_layouts, []);

    // Ensure that only selected grid layouts are saved into configuration.
    $edit = [
      'behavior_plugins[grid_layout][enabled]' => TRUE,
      'behavior_plugins[grid_layout][settings][paragraph_reference_field]' => 'paragraphs_container_paragraphs',
      'behavior_plugins[grid_layout][settings][available_grid_layouts][paragraphs_collection_test_two_column]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/paragraphs_type/grid', $edit, 'Save');
    $saved_grid_layouts = \Drupal::config('paragraphs.paragraphs_type.grid')->get('behavior_plugins.grid_layout.available_grid_layouts');
    $this->assertEqual($saved_grid_layouts, ['paragraphs_collection_test_two_column' => 'paragraphs_collection_test_two_column']);
  }

}
