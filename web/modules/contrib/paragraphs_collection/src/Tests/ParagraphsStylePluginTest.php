<?php

namespace Drupal\paragraphs_collection\Tests;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Tests\Experimental\ParagraphsExperimentalTestBase;

/**
 * Tests the style selection plugin.
 *
 * @see \Drupal\paragraphs_collection\Plugin\paragraphs\Behavior\ParagraphsStylePlugin
 * @group paragraphs_collection
 */
class ParagraphsStylePluginTest extends ParagraphsExperimentalTestBase {

  /**
   * Modules to be enabled.
   */
  public static $modules = [
    'paragraphs_collection',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests the advanced style functionality.
   */
  public function testAdvancedStyles() {
    // Install Paragraph Collection Test in order to have styles.
    \Drupal::service('module_installer')->install(['paragraphs_collection_test']);

    $this->addParagraphedContentType('paragraphed_test', 'paragraphs');
    $this->loginAsAdmin([
      'edit any paragraphed_test content',
      'edit behavior plugin settings',
      'use advanced style',
    ]);
    $this->drupalGet('admin/structure/paragraphs_type/add');

    // Create Paragraph type with Style plugin enabled.
    $paragraph_type = 'test_style_plugin';
    $this->addParagraphsType($paragraph_type);
    // Add a text field.
    $this->fieldUIAddExistingField('admin/structure/paragraphs_type/' . $paragraph_type, 'paragraphs_text', $paragraph_type);
    $edit = [
      'behavior_plugins[style][enabled]' => TRUE,
    ];
    $this->drupalPostForm('admin/structure/paragraphs_type/' . $paragraph_type, $edit, t('Save'));
    $edit = [
      'behavior_plugins[style][settings][groups][advanced_test_group]' => TRUE,
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'behavior_plugins[style][settings][groups][advanced_test_group]');
    $edit = [
      'behavior_plugins[style][settings][groups][advanced_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups_defaults][advanced_test_group][default]' => '',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Create paragraphed content.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_test_style_plugin_add_more');

    // Assert a user has no access to super advanced style.
    $styles = $this->xpath('//select[contains(@id, :id)]', [':id' => 'edit-paragraphs-0-behavior-plugins-style-style']);
    $this->assertEqual(2, count($styles[0]->option));
    $this->assertEqual('- Default -', $styles[0]->option[0]);
    $this->assertEqual('Advanced', $styles[0]->option[1]);

    // Apply advanced style.
    $edit = [
      'title[0][value]' => 'advanced_style',
      'paragraphs[0][subform][paragraphs_text][0][value]' => 'I am text enhanced with advanced style.',
      'paragraphs[0][behavior_plugins][style][style_wrapper][styles][advanced_test_group]' => 'advanced',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    // Advanced style has been applied.
    $this->assertEqual(count($this->cssSelect('.paragraphs-behavior-style--advanced')), 1);
    // Assert that the attributes are visible.
    $this->assertEqual(count($this->cssSelect('[data-attribute="test"]')), 1);

    // Set advanced style as a default one.
    $edit = [
      'behavior_plugins[style][settings][groups][advanced_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups_defaults][advanced_test_group][default]' => 'advanced',
    ];
    $this->drupalPostForm('admin/structure/paragraphs_type/' . $paragraph_type, $edit, t('Save'));

    // Anonymous users still see the advanced style applied.
    $node = $this->getNodeByTitle('advanced_style');
    $this->drupalLogout();
    $this->drupalGet($node->toUrl());
    $this->assertEqual(count($this->cssSelect('.paragraphs-behavior-style--advanced')), 1);

    // Advanced style can not be changed without the style permission.
    $this->loginAsAdmin([
      'edit any paragraphed_test content',
      'edit behavior plugin settings',
      'use super-advanced style',
    ]);
    $this->drupalGet($node->toUrl('edit-form'));

    // User cannot update the advanced style.
    $styles = $this->xpath('//select[@name="paragraphs[0][behavior_plugins][style][style_wrapper][styles][advanced_test_group]"]');
    $this->assertEqual('disabled', (string) $styles[0]['disabled']);

    // As the user can not access advanced style and as with super-advanced
    // style there would be only element in the list, no style selection is
    // displayed.
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_test_style_plugin_add_more');
    $styles = $this->xpath('//select[@name="paragraphs[1][behavior_plugins][style][style_wrapper][styles][advanced_test_group]"]');
    $this->assertEqual(0, count($styles));
    $this->drupalPostForm(NULL, [], 'Save');
    // The advanced (default) style was applied to the second text paragraph.
    $this->assertEqual(count($this->cssSelect('.paragraphs-behavior-style--advanced')), 2);
  }

  /**
   * Tests the style selection plugin settings and functionality.
   */
  public function testStyleSelection() {
    // Install Paragraph Collection Test in order to have styles.
    \Drupal::service('module_installer')->install(['paragraphs_collection_test']);

    $this->addParagraphedContentType('paragraphed_test', 'paragraphs');
    $this->loginAsAdmin([
      'edit any paragraphed_test content',
      'edit behavior plugin settings',
    ]);
    $this->drupalGet('admin/structure/paragraphs_type/add');

    // Create Paragraph type with Style plugin enabled.
    $paragraph_type = 'test_style_plugin';
    $this->addParagraphsType($paragraph_type);
    // Add a text field.
    $this->fieldUIAddExistingField('admin/structure/paragraphs_type/' . $paragraph_type, 'paragraphs_text', $paragraph_type);
    $this->drupalGet('admin/structure/paragraphs_type/' . $paragraph_type);
    $edit = [
      'behavior_plugins[style][enabled]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('The style plugin cannot be enabled if no groups are selected.');
    $edit = [
      'behavior_plugins[style][settings][groups][regular_test_group]' => TRUE,
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'behavior_plugins[style][settings][groups][regular_test_group]');
    $edit = [
      'behavior_plugins[style][settings][groups][regular_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups_defaults][regular_test_group][default]' => '',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Create paragraphed content.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_test_style_plugin_add_more');

    // Check that we have style plugin.
    $this->assertText('style');
    $this->assertField('paragraphs[0][behavior_plugins][style][style_wrapper][styles][regular_test_group]');

    // Check that the style options are sorted alphabetically.
    $styles = $this->xpath('//select[contains(@id, :id)]', [':id' => 'edit-paragraphs-0-behavior-plugins-style-style']);
    $this->assertEqual('- Default -', $styles[0]->option[0]);
    $this->assertEqual('Bold', $styles[0]->option[1]);
    $this->assertEqual('Overridden style Module', $styles[0]->option[2]);
    $this->assertEqual('Regular', $styles[0]->option[3]);

    // Restrict the paragraphs type to the "Italic Test Group" style group.
    $this->drupalGet('admin/structure/paragraphs_type/' . $paragraph_type);
    $this->assertFieldByName('behavior_plugins[style][settings][groups][italic_test_group]');
    $edit = [
      'behavior_plugins[style][enabled]' => TRUE,
      'behavior_plugins[style][settings][groups][italic_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups][regular_test_group]' => FALSE,
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'behavior_plugins[style][settings][groups][italic_test_group]');
    $edit = [
      'behavior_plugins[style][settings][groups][italic_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups][regular_test_group]' => FALSE,
      'behavior_plugins[style][settings][groups_defaults][italic_test_group][default]' => '',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Check that the style without a style group is no longer available.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_test_style_plugin_add_more');

    // Since Italic Group defines only two styles, assert that only they appear.
    $styles = $this->xpath('//select[contains(@id, :id)]', [':id' => 'edit-paragraphs-0-behavior-plugins-style-style']);
    $this->assertEqual(3, count($styles[0]->option));
    $this->assertEqual('- Default -', $styles[0]->option[0]);
    $this->assertEqual('Bold', $styles[0]->option[1]);
    $this->assertEqual('Italic', $styles[0]->option[2]);

    // Configure Regular as a default style.
    $this->drupalGet('admin/structure/paragraphs_type/' . $paragraph_type);
    $this->assertFieldByName('behavior_plugins[style][settings][groups_defaults][italic_test_group][default]', '');
    $edit = [
      'behavior_plugins[style][settings][groups][regular_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups][italic_test_group]' => FALSE,
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'behavior_plugins[style][settings][groups][regular_test_group]');
    $edit = [
      'behavior_plugins[style][settings][groups][regular_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups][italic_test_group]' => FALSE,
      'behavior_plugins[style][settings][groups_defaults][regular_test_group][default]' => 'regular',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Regular style should be shown first in the list.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_test_style_plugin_add_more');
    $this->assertOptionSelectedWithDrupalSelector('edit-paragraphs-0-behavior-plugins-style-style-wrapper-styles-regular-test-group', 'regular');
    $styles = $this->xpath('//select[contains(@id, :id)]', [':id' => 'edit-paragraphs-0-behavior-plugins-style-style']);
    $this->assertEqual(3, count($styles[0]->option));
    $this->assertEqual('- Regular -', $styles[0]->option[0]);
    $this->assertEqual('Bold', $styles[0]->option[1]);

    // Default style should be applied.
    $edit = [
      'title[0][value]' => 'style_plugin_node',
      'paragraphs[0][subform][paragraphs_text][0][value]' => 'I am regular text.',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Assert the theme suggestion added by the style plugin.
    $this->assertText('paragraph__test_style_plugin__regular');

    $style = $this->xpath('//div[@class="regular regular-wrapper paragraphs-behavior-style--regular paragraph paragraph--type--test-style-plugin paragraph--view-mode--default"]')[0];
    $this->assertNotNull($style);

    // Assert default value for the style selection.
    $node = $this->getNodeByTitle('style_plugin_node');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertFieldByName('paragraphs[0][behavior_plugins][style][style_wrapper][styles][regular_test_group]', 'regular');

    // Update the styles group configuration.
    $this->drupalGet('admin/structure/paragraphs_type/' . $paragraph_type);
    $edit = [
      'behavior_plugins[style][enabled]' => TRUE,
      'behavior_plugins[style][settings][groups][bold_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups][overline_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups][empty_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups][regular_test_group]' => TRUE,
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'behavior_plugins[style][settings][groups][bold_test_group]');
    $edit = [
      'behavior_plugins[style][enabled]' => TRUE,
      'behavior_plugins[style][settings][groups_defaults][bold_test_group][default]' => 'bold',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Assert the values on the behavior form.
    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertNoText('Bold CONTEXT');
    $this->assertNoText('Empty Test Group');
    // Regular and Overline style groups are visible.
    $this->assertOptionSelected('edit-paragraphs-0-behavior-plugins-style-style-wrapper-styles-regular-test-group', 'regular', 'Regular style group has a default option applied.');
    $this->assertOptionSelected('edit-paragraphs-0-behavior-plugins-style-style-wrapper-styles-overline-test-group', '', 'There is no configured default value for Overline style group.');
    // Bold and Empty style groups are not visible as they have exactly one
    // item in the list.
    $this->assertNoRaw('edit-paragraphs-0-behavior-plugins-style-style-wrapper-styles-bold-test-group');
    $this->assertNoRaw('edit-paragraphs-0-behavior-plugins-style-style-wrapper-styles-empty-test-group');
    $this->drupalPostForm(NULL, [], 'Save');

    // Regular style has been selected through the form.
    $this->assertEqual(count($this->cssSelect('.paragraphs-behavior-style--regular')), 1);
    // Default Bold style has been applied in the background.
    $this->assertEqual(count($this->cssSelect('.paragraphs-behavior-style--bold')), 1);
    // Overline style has not been applied as it has no default option.
    $this->assertEqual(count($this->cssSelect('.paragraphs-behavior-style--overline')), 0);
    // Empty style has not been applied as it has no default option nor styles.
    $this->assertEqual(count($this->cssSelect('.paragraphs-behavior-style--empty')), 0);

    $edit = [
      // Set default style for the overline group.
      'behavior_plugins[style][settings][groups_defaults][overline_test_group][default]' => 'overline',
      // Remove default style for the bold group.
      'behavior_plugins[style][settings][groups_defaults][bold_test_group][default]' => '',
    ];
    $this->drupalPostForm('admin/structure/paragraphs_type/' . $paragraph_type, $edit, 'Save');
    $edit = [
      'styles[bold][enabled]' => TRUE,
      'styles[italic][enabled]' => TRUE,
      'styles[underline][enabled]' => TRUE,
      'styles[overline][enabled]' => TRUE,
      // Disable regular style.
      'styles[regular][enabled]' => FALSE,
    ];
    $this->drupalPostForm('admin/reports/paragraphs_collection/styles', $edit, 'Save configuration');

    $this->drupalGet($node->toUrl());
    // The new default overline style applies to the previously saved paragraph.
    $this->assertEqual(count($this->cssSelect('.paragraphs-behavior-style--overline')), 1);
    // The bold style has no default and no longer applies.
    $this->assertEqual(count($this->cssSelect('.paragraphs-behavior-style--bold')), 0);
    // The regular style is disabled and no longer applies.
    $this->assertEqual(count($this->cssSelect('.paragraphs-behavior-style--regular')), 0);

    // Default overline style is selected and all overline styles are disabled.
    // The empty form element should not be displayed.
    $edit = [
      'styles[bold][enabled]' => FALSE,
      'styles[italic][enabled]' => TRUE,
      'styles[underline][enabled]' => TRUE,
      // Disable overline style.
      'styles[overline][enabled]' => FALSE,
      'styles[regular][enabled]' => FALSE,
    ];
    $this->drupalPostForm('admin/reports/paragraphs_collection/styles', $edit, 'Save configuration');
    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertNoFieldByName('paragraphs[0][behavior_plugins][style][style_wrapper][styles][overline_test_group]');
    $this->assertNoRaw('edit-paragraphs-0-behavior-plugins-style');
  }

  /**
   * Tests style settings summary.
   */
  public function testStyleSettingsSummary() {
    // Install Paragraph Collection Test in order to have styles.
    \Drupal::service('module_installer')->install(['paragraphs_collection_test']);

    $this->addParagraphedContentType('paragraphed_test');
    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'edit behavior plugin settings',
    ]);

    // Create text paragraph.
    $text_paragraph = Paragraph::create([
      'type' => 'text',
      'paragraphs_text' => [
        'value' => '<p>Test text 1.</p>',
        'format' => 'basic_html',
      ],
    ]);
    $text_paragraph->save();

    // Create a container paragraph for the text paragraph.
    $paragraph = Paragraph::create([
      'title' => 'Demo Paragraph',
      'type' => 'container',
      'paragraphs_container_paragraphs' => [$text_paragraph],
    ]);
    $paragraph->save();

    // Create a node with the paragraphs content.
    $node = Node::create([
      'title' => 'Style plugin test',
      'type' => 'paragraphed_test',
      'field_paragraphs' => [$paragraph],
    ]);
    $node->save();

    // Check the empty summary.
    $behavior_plugins = $paragraph->getParagraphType()->get('behavior_plugins');
    $behavior_plugins['style'] = [
      'enabled' => TRUE,
      'groups' => ['bold_test_group' => ['default' => '']],
    ];
    $paragraph->getParagraphType()->set('behavior_plugins', $behavior_plugins);
    $paragraph->getParagraphType()->save();
    $style_plugin = $paragraph->getParagraphType()->getEnabledBehaviorPlugins()['style'];
    $this->assertEqual([], $style_plugin->settingsSummary($paragraph));

    // Use bold style for this container.
    $paragraph->setBehaviorSettings('style', ['styles' => ['bold_test_group' => 'bold']]);
    $paragraph->save();
    $this->assertEqual([['label' => 'Bold CONTEXT', 'value' => 'Bold']], $style_plugin->settingsSummary($paragraph));

    $this->drupalGet('admin/structure/paragraphs_type/' . $paragraph->getParagraphType()->id());
    $this->assertText('Bold Test Group');

    // Check the settings summary in a closed mode.
    $this->setParagraphsWidgetMode('paragraphed_test', 'field_paragraphs', 'closed');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertRaw('edit-field-paragraphs-0-top-icons');
    $this->assertRaw('<span class="summary-content">Test text 1.</span></div><div class="paragraphs-plugin-wrapper"><span class="summary-plugin"><span class="summary-plugin-label">Bold CONTEXT</span>Bold');

    // Configure style bold as default.
    $edit = [
      'behavior_plugins[style][settings][groups][bold_test_group]' => TRUE,
    ];
    $this->drupalPostAjaxForm('admin/structure/paragraphs_type/' . $paragraph->getType(), $edit, 'behavior_plugins[style][settings][groups][bold_test_group]');
    $edit = [
      'behavior_plugins[style][settings][groups][bold_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups_defaults][bold_test_group][default]' => 'bold',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Check that the settings summary does not show the default style.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertRaw('<span class="summary-content">Test text 1.');
    $this->assertNoRaw('Style: Bold');
    $this->assertNoRaw('Style: - Bold -');
  }

  /**
   * Tests style plugin with no styles available.
   */
  public function testNoStylesAvailable() {
    $this->addParagraphedContentType('paragraphed_test', 'paragraphs');
    $this->loginAsAdmin([
      'edit any paragraphed_test content',
      'edit behavior plugin settings',
    ]);
    $this->drupalGet('admin/structure/paragraphs_type/add');

    // Create Paragraph type with Style plugin enabled.
    $paragraph_type = 'test_style_plugin';
    $this->addParagraphsType($paragraph_type);
    // Add a text field.
    $this->fieldUIAddExistingField('admin/structure/paragraphs_type/' . $paragraph_type, 'paragraphs_text');
    $this->drupalGet('admin/structure/paragraphs_type/' . $paragraph_type);
    $options = $this->xpath('//*[contains(@id,"edit-behavior-plugins-style-settings-groups")]/option');
    $this->assertEqual(0, count($options));
    $edit = [
      'behavior_plugins[style][enabled]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Make sure there is an error message shown for the style group.
    $this->assertText('There is no style group available, the style plugin can not be enabled.');
  }

  /**
   * Tests global settings for style plugin.
   */
  public function testGlobalStyleSettings() {
    // Install paragraphs collection test to use test style plugins.
    \Drupal::service('module_installer')->install(['paragraphs_collection_test']);
    $this->addParagraphedContentType('paragraphed_test', 'paragraphs');
    $this->loginAsAdmin([
      'edit any paragraphed_test content',
      'edit behavior plugin settings',
    ]);

    // Create Paragraph type with Style plugin enabled.
    $paragraph_type = 'test_style_plugin';
    $this->addParagraphsType($paragraph_type);
    // Add a text field.
    $this->fieldUIAddExistingField('admin/structure/paragraphs_type/' . $paragraph_type, 'paragraphs_text');
    $this->drupalGet('admin/structure/paragraphs_type/' . $paragraph_type);
    $edit = [
      'behavior_plugins[style][enabled]' => TRUE,
      'behavior_plugins[style][settings][groups][bold_test_group]' => TRUE,
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'behavior_plugins[style][settings][groups][bold_test_group]');
    $edit = [
      'behavior_plugins[style][enabled]' => TRUE,
      'behavior_plugins[style][settings][groups][regular_test_group]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->drupalGet('admin/structure/paragraphs_type/' . $paragraph_type);

    // Assert global settings.
    $this->drupalGet('admin/reports/paragraphs_collection/styles');
    $this->assertFieldByName('styles[bold][enabled]', FALSE);
    $this->assertFieldByName('styles[italic][enabled]', FALSE);
    $this->assertFieldByName('styles[regular][enabled]', FALSE);
    $this->assertFieldByName('styles[underline][enabled]', FALSE);

    // Add a node with paragraphs and check the available styles.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, NULL, 'paragraphs_' . $paragraph_type . '_add_more');
    $options = $this->xpath('//*[contains(@class,"paragraphs-plugin-form-element")]/option');
    $this->assertEqual(2, count($options));
    $edit = [
      'title[0][value]' => 'global_settings',
      'paragraphs[0][behavior_plugins][style][style_wrapper][styles][bold_test_group]' => 'bold'
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertRaw('paragraphs-behavior-style--bold');

    $edit = [
      'behavior_plugins[style][enabled]' => TRUE,
      'behavior_plugins[style][settings][groups][italic_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups][bold_test_group]' => FALSE,
    ];
    $this->drupalPostAjaxForm('admin/structure/paragraphs_type/' . $paragraph_type, $edit, 'behavior_plugins[style][settings][groups][italic_test_group]');
    $edit = [
      'behavior_plugins[style][enabled]' => TRUE,
      'behavior_plugins[style][settings][groups][italic_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups][bold_test_group]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Update global settings and enable two styles.
    $this->drupalGet('admin/reports/paragraphs_collection/styles');
    $edit = [
      'styles[italic][enabled]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save configuration');
    $node = $this->getNodeByTitle('global_settings');
    $this->drupalGet('node/' . $node->id());
    // Assert that the class of the plugin is not added if disabled.
    $this->assertNoRaw('paragraphs-behavior-style--bold');
    $this->clickLink('Edit');
    // Assert that only the two enabled styles are available.
    $options = $this->xpath('//*[contains(@class,"paragraphs-plugin-form-element")]/option');
    $this->assertEqual(2, count($options));
    $this->assertEqual($options[0], '- Default -');
    $this->assertEqual($options[1], 'Italic');

    $this->drupalGet('admin/structure/paragraphs_type/' . $paragraph_type);
    $options = $this->xpath('//*[contains(@name,"behavior_plugins[style][settings][groups_defaults][italic_test_group][default]")]/option');
    $this->assertEqual(2, count($options));
    $this->assertEqual($options[0], '- None -');
    $this->assertEqual($options[1], 'Italic');

    // Enable bold and italic styles.
    $edit = [
      'styles[bold][enabled]' => TRUE,
      'styles[italic][enabled]' => TRUE,
    ];
    $this->drupalPostForm('admin/reports/paragraphs_collection/styles', $edit, 'Save configuration');
    // Set default style to italic.
    $edit = [
      'behavior_plugins[style][enabled]' => TRUE,
      'behavior_plugins[style][settings][groups][italic_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups][bold_test_group]' => FALSE,
    ];
    $this->drupalPostAjaxForm('admin/structure/paragraphs_type/' . $paragraph_type, $edit, 'behavior_plugins[style][settings][groups][italic_test_group]');
    $edit = [
      'behavior_plugins[style][settings][groups][italic_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups_defaults][italic_test_group][default]' => 'italic',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Set the paragraph style to bold.
    $this->drupalPostForm('node/' . $node->id() . '/edit', ['paragraphs[0][behavior_plugins][style][style_wrapper][styles][italic_test_group]' => 'bold'], t('Save'));
    $this->assertRaw('paragraphs-behavior-style--bold');
    // Assert that the selection is correctly displayed.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertOptionSelected('edit-paragraphs-0-behavior-plugins-style-style-wrapper-styles-italic-test-group', 'bold');

    // Disable the bold style.
    $this->drupalGet('admin/reports/paragraphs_collection/styles');
    $edit = [
      'styles[bold][enabled]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save configuration');
    // The plugin should fallback on the default style defined.
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw('paragraphs-behavior-style--italic');
  }

  /**
   * Tests the multiple style selection plugin settings and functionality.
   */
  public function testMultipleGroups() {
    // Install Paragraph Collection Test in order to have styles.
    \Drupal::service('module_installer')
      ->install(['paragraphs_collection_test']);

    $this->addParagraphedContentType('paragraphed_test', 'paragraphs');
    $this->loginAsAdmin([
      'edit any paragraphed_test content',
      'edit behavior plugin settings',
    ]);
    $this->drupalGet('admin/structure/paragraphs_type/add');

    // Create Paragraph type with Style plugin enabled.
    $paragraph_type = 'test_style_plugin';
    $this->addParagraphsType($paragraph_type);
    // Add a text field.
    $this->fieldUIAddExistingField('admin/structure/paragraphs_type/' . $paragraph_type, 'paragraphs_text', $paragraph_type);

    // Restrict the paragraphs type to the "Italic Test Group" style group.
    $this->drupalGet('admin/structure/paragraphs_type/' . $paragraph_type);
    $edit = [
      'behavior_plugins[style][enabled]' => TRUE,
      'behavior_plugins[style][settings][groups][italic_test_group]' => TRUE,
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'behavior_plugins[style][settings][groups][italic_test_group]');
    $edit = [
      'behavior_plugins[style][enabled]' => TRUE,
      'behavior_plugins[style][settings][groups][italic_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups_defaults][italic_test_group][default]' => 'italic',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Create a paragraphed test node and check the style classes.
    $this->drupalGet('node/add/paragraphed_test');
    $this->drupalPostAjaxForm(NULL, [], 'paragraphs_test_style_plugin_add_more');
    // Since Italic Group defines only two styles, assert that only they appear.
    $styles = $this->xpath('//select[contains(@id, :id)]', [':id' => 'edit-paragraphs-0-behavior-plugins-style-style']);
    $this->assertEqual(2, count($styles[0]->option));
    $this->assertEqual('- Italic -', $styles[0]->option[0]);
    $this->assertEqual('Bold', $styles[0]->option[1]);
    $edit = [
      'title[0][value]' => 'title_to_remember',
      'paragraphs[0][subform][paragraphs_text][0][value]' => 'text to apply styles'
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertRaw('paragraphs-behavior-style--italic');

    // Configure two groups and set their defaults.
    $this->drupalGet('admin/structure/paragraphs_type/' . $paragraph_type);
    $edit = [
      'behavior_plugins[style][settings][groups][italic_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups][regular_test_group]' => TRUE,
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'behavior_plugins[style][settings][groups][italic_test_group]');
    $edit = [
      'behavior_plugins[style][settings][groups][italic_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups][regular_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups_defaults][regular_test_group][default]' => 'regular',
      'behavior_plugins[style][settings][groups_defaults][italic_test_group][default]' => 'italic',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Check the selects elements for each enabled group and check the classes.
    $node = $this->getNodeByTitle('title_to_remember');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $styles = $this->xpath('//select[contains(@name, :name)]', [':name' => 'paragraphs[0][behavior_plugins][style][style_wrapper][styles][regular_test_group]']);
    $this->assertEqual(3, count($styles[0]->option));
    $this->assertEqual('- Regular -', $styles[0]->option[0]);
    $this->assertEqual('Bold', $styles[0]->option[1]);
    $styles = $this->xpath('//select[contains(@name, :name)]', [':name' => 'paragraphs[0][behavior_plugins][style][style_wrapper][styles][italic_test_group]']);
    $this->assertEqual(2, count($styles[0]->option));
    $this->assertEqual('- Italic -', $styles[0]->option[0]);
    $this->assertEqual('Bold', $styles[0]->option[1]);
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw('paragraphs-behavior-style--italic');
    $this->assertRaw('paragraphs-behavior-style--regular');

    // Configure Regular as a default style.
    $this->drupalGet('admin/structure/paragraphs_type/' . $paragraph_type);
    $edit = [
      'behavior_plugins[style][settings][groups][regular_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups][italic_test_group]' => FALSE,
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'behavior_plugins[style][settings][groups][regular_test_group]');
    $edit = [
      'behavior_plugins[style][settings][groups][regular_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups_defaults][regular_test_group][default]' => 'bold',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Check that there is only one select and only one style class.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $styles = $this->xpath('//select[contains(@name, :name)]', [':name' => 'paragraphs[0][behavior_plugins][style][style_wrapper][styles][regular_test_group]']);
    $this->assertEqual(3, count($styles[0]->option));
    $this->assertEqual('- Bold -', $styles[0]->option[0]);
    $this->assertEqual('Overridden style Module', $styles[0]->option[1]);
    $this->assertEqual('Regular', $styles[0]->option[2]);
    $styles = $this->xpath('//select[contains(@name, :name)]', [':name' => 'paragraphs[0][behavior_plugins][style][style_wrapper][styles][italic_test_group]']);
    $this->assertEqual([], $styles);
    $this->drupalGet('node/' . $node->id());
    $this->assertNoRaw('paragraphs-behavior-style--italic');
    $this->assertRaw('paragraphs-behavior-style--bold');

    // Configure Regular as a default style.
    $this->drupalGet('admin/structure/paragraphs_type/' . $paragraph_type);
    $edit = [
      'behavior_plugins[style][settings][groups][regular_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups][italic_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups][underline_test_group]' => TRUE,
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'behavior_plugins[style][settings][groups][italic_test_group]');
    $edit = [
      'behavior_plugins[style][settings][groups][regular_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups][italic_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups][underline_test_group]' => TRUE,
      'behavior_plugins[style][settings][groups_defaults][italic_test_group][default]' => 'italic',
      'behavior_plugins[style][settings][groups_defaults][regular_test_group][default]' => 'regular',
      'behavior_plugins[style][settings][groups_defaults][underline_test_group][default]' => 'underline',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Check that there is only one select and only one style class.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $styles = $this->xpath('//select[contains(@name, :name)]', [':name' => 'paragraphs[0][behavior_plugins][style][style_wrapper][styles][regular_test_group]']);
    $this->assertEqual(3, count($styles[0]->option));
    $this->assertEqual('- Regular -', $styles[0]->option[0]);
    $this->assertEqual('Bold', $styles[0]->option[1]);
    $styles = $this->xpath('//select[contains(@name, :name)]', [':name' => 'paragraphs[0][behavior_plugins][style][style_wrapper][styles][italic_test_group]']);
    $this->assertEqual(2, count($styles[0]->option));
    $this->assertEqual('- Italic -', $styles[0]->option[0]);
    $this->assertEqual('Bold', $styles[0]->option[1]);
    $styles = $this->xpath('//select[contains(@name, :name)]', [':name' => 'paragraphs[0][behavior_plugins][style][style_wrapper][styles][underline_test_group]']);
    $this->assertEqual(2, count($styles[0]->option));
    $this->assertEqual('- Underline -', $styles[0]->option[0]);
    $this->assertEqual('Bold', $styles[0]->option[1]);
    $this->drupalGet('node/' . $node->id());
    $this->assertRaw('paragraphs-behavior-style--italic');
    $this->assertRaw('paragraphs-behavior-style--regular');
    $this->assertRaw('paragraphs-behavior-style--underline');

    // Change a plugin.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $edit = [
      'paragraphs[0][behavior_plugins][style][style_wrapper][styles][regular_test_group]' => 'bold'
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertRaw('paragraphs-behavior-style--italic');
    $this->assertRaw('paragraphs-behavior-style--bold');
    $this->assertRaw('paragraphs-behavior-style--underline');
    // Assert the theme suggestion added by the style plugin.
    $this->assertText('paragraph__test_style_plugin__bold');
    $this->assertText('paragraph__test_style_plugin__italic');
  }

  /**
   * Tests the style overriding with sub themes.
   */
  public function testStyleOverriding() {
    // Install theme c and assert that the gotten style has the class "c".
    \Drupal::service('module_installer')->install(['paragraphs_collection_test']);

    $style_discovery = \Drupal::getContainer()->get('paragraphs_collection.style_discovery');
    $style = $style_discovery->getStyle('style-overridden');
    $this->assertEqual($style['title'], new TranslatableMarkup('Overridden style Module'));
    $this->assertEqual($style['classes'], ['overridden-style-module']);

    \Drupal::service('theme_installer')->install(['paragraphs_collection_test_theme_a']);
    $style = $style_discovery->getStyle('style-overridden');
    $this->assertEqual($style['title'], new TranslatableMarkup('Overridden style A'));
    $this->assertEqual($style['classes'], ['overridden-style-a']);

    \Drupal::service('theme_installer')->uninstall(['paragraphs_collection_test_theme_a']);
    $style = $style_discovery->getStyle('style-overridden');
    $this->assertEqual($style['title'], new TranslatableMarkup('Overridden style C'));
    $this->assertEqual($style['classes'], ['overridden-style-c']);
  }

  /**
   * Tests the style template picking.
   */
  public function testStyleTemplate() {
    // Install paragraphs collection test to use test style plugins.
    \Drupal::service('module_installer')->install(['paragraphs_collection_test']);
    \Drupal::service('theme_installer')->install(['paragraphs_collection_test_theme_a']);
    $theme_config = \Drupal::configFactory()->getEditable('system.theme');
    $theme_config->set('default', 'paragraphs_collection_test_theme_a');
    $theme_config->save();
    $this->addParagraphedContentType('paragraphed_test', 'paragraphs');
    $this->loginAsAdmin([
      'edit any paragraphed_test content',
      'edit behavior plugin settings',
    ]);
    // Enable the style plugin.
    $this->drupalGet('admin/structure/paragraphs_type/separator');
    $edit = [
      'behavior_plugins[style][enabled]' => TRUE,
      'behavior_plugins[style][settings][groups][regular_test_group]' => TRUE,
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'behavior_plugins[style][settings][groups][regular_test_group]');
    $edit = [
      'behavior_plugins[style][enabled]' => TRUE,
      'behavior_plugins[style][settings][groups][regular_test_group]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Add a Separator paragraph and check if it uses the paragraph type
    // template.
    $this->drupalPostAjaxForm('node/add/paragraphed_test', [], 'paragraphs_separator_add_more');
    $edit = [
      'title[0][value]' => 'test_title',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    // Assert that the Paragraph type template is used.
    $this->assertUniqueText('paragraph-type-template');
    $this->assertNoText('paragraph-style-template');
    // Set the style for the paragraphs and check if it uses the style template.
    $node = $this->getNodeByTitle('test_title');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $edit = [
      'paragraphs[0][behavior_plugins][style][style_wrapper][styles][regular_test_group]' => 'style-overridden',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    // Assert that the Style template is used.
    $this->assertUniqueText('paragraph-style-template');
    $this->assertNoText('paragraph-type-template');
  }

}
