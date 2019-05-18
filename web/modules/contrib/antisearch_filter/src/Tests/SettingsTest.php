<?php

namespace Drupal\antisearch_filter\Tests;

/**
 * Tests the settings functionality of the antisearch filter module.
 *
 * @group antisearch_filter
 */
class SettingsTest extends AntisearchWebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['antisearch_filter'];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser([
      'administer filters',
    ]);
    $this->drupalLogin($this->user);
  }

  /**
   * Get the filter settings.
   *
   * @pararm string $format_name
   *   The machine name of the text format.
   */
  protected function getFilterSettings($format_name) {
    filter_formats_reset();
    $formats = filter_formats();
    $basic_html = $formats[$format_name];
    $filters = $basic_html->filters()->getAll();
    $antisearch_filter = $filters['filter_antisearch'];
    return $antisearch_filter->settings;
  }

  /**
   * Tests the format administration functionality.
   */
  public function testFormatAdmin() {
    // Check format add page.
    $this->drupalGet('admin/config/content/formats/add');
    $this->assertText(t('Antisearch filter'));
    $this->assertText(t('Hide text from search engines like Google. The filter adds random characters between the single characters of the text.'));
    $this->assertText(t('Apply to e-mail adresses.'));
    $this->assertText(t('Apply antisearch filter to e-mail addresses (e. g. foo@bar.com).'));
    $this->assertText(t('Apply to HTML strike tags.'));
    $this->assertText(t('Apply antisearch filter to text surrounded by html strike tags (e. g. &lt;strike&gt;foo bar&lt;/strike&gt;).'));
    $this->assertText(t('Apply to square brackets.'));
    $this->assertText(t('Apply antisearch filter to text surrounded by square brackets (e. g. [foo bar]).'));
    $this->assertText(t('Show description.'));
    $this->assertText(t('Show description.'));

    // Add new format.
    $format_id = 'antisearch_filter_format';
    $name = 'Antisearch filter format';
    $edit = [
      'format' => $format_id,
      'name' => $name,
      'roles[anonymous]' => 1,
      'roles[authenticated]' => 1,
      'filters[filter_antisearch][status]' => 1,
      'filters[filter_antisearch][settings][antisearch_filter_email]' => 1,
      'filters[filter_antisearch][settings][antisearch_filter_strike]' => 1,
      'filters[filter_antisearch][settings][antisearch_filter_bracket]' => 1,
      'filters[filter_antisearch][settings][antisearch_filter_show_title]' => 1,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    // Show submitted format edit page.
    $this->drupalGet('admin/config/content/formats/manage/antisearch_filter_format');

    $input = $this->xpath('//input[@id="edit-filters-filter-antisearch-status"]');
    $this->assertEqual($input[0]->attributes()->checked, 'checked');

    $input = $this->xpath('//input[@id="edit-filters-filter-antisearch-settings-antisearch-filter-email"]');
    $this->assertEqual($input[0]->attributes()->checked, 'checked');

    $input = $this->xpath('//input[@id="edit-filters-filter-antisearch-settings-antisearch-filter-strike"]');
    $this->assertEqual($input[0]->attributes()->checked, 'checked');

    $input = $this->xpath('//input[@id="edit-filters-filter-antisearch-settings-antisearch-filter-bracket"]');
    $this->assertEqual($input[0]->attributes()->checked, 'checked');

    $input = $this->xpath('//input[@id="edit-filters-filter-antisearch-settings-antisearch-filter-show-title"]');
    $this->assertEqual($input[0]->attributes()->checked, 'checked');

    // Test format object.
    filter_formats_reset();
    $formats = filter_formats();
    $this->assertIdentical($formats[$format_id]->get('name'), $name);

    // Check format overview page.
    $this->drupalGet('admin/config/content/formats');
    $this->assertText($name);
  }

  /**
   * Create text formats with different antisearch filter settings.
   */
  public function testDifferentSettings() {
    // Defaults.
    $this->createTextFormatWeb('defaults');
    $settings = $this->getFilterSettings('defaults');
    $this->assertEqual($settings['antisearch_filter_email'], TRUE);
    $this->assertEqual($settings['antisearch_filter_strike'], TRUE);
    $this->assertEqual($settings['antisearch_filter_bracket'], TRUE);
    $this->assertEqual($settings['antisearch_filter_show_title'], TRUE);

    // Set all settings to false.
    $this->createTextFormatWeb('all_true', [
      'antisearch_filter_email' => TRUE,
      'antisearch_filter_strike' => TRUE,
      'antisearch_filter_bracket' => TRUE,
      'antisearch_filter_show_title' => TRUE,
    ]);
    $settings = $this->getFilterSettings('all_true');
    $this->assertEqual($settings['antisearch_filter_email'], TRUE);
    $this->assertEqual($settings['antisearch_filter_strike'], TRUE);
    $this->assertEqual($settings['antisearch_filter_bracket'], TRUE);
    $this->assertEqual($settings['antisearch_filter_show_title'], TRUE);

    // Set all settings to false.
    $this->createTextFormatWeb('all_false', [
      'antisearch_filter_email' => FALSE,
      'antisearch_filter_strike' => FALSE,
      'antisearch_filter_bracket' => FALSE,
      'antisearch_filter_show_title' => FALSE,
    ]);
    $settings = $this->getFilterSettings('all_false');
    $this->assertEqual($settings['antisearch_filter_email'], FALSE);
    $this->assertEqual($settings['antisearch_filter_strike'], FALSE);
    $this->assertEqual($settings['antisearch_filter_bracket'], FALSE);
    $this->assertEqual($settings['antisearch_filter_show_title'], FALSE);
  }

  /**
   * Create some text formats using the configuration management.
   */
  public function testCreateTextFormatsUsingConfigurationManagement() {
    $format_names = ['all', 'bracket', 'email', 'none', 'show_title', 'strike'];

    foreach ($format_names as $format_name) {
      $this->createTextFormatProgrammatically($format_name);
    }

    foreach ($format_names as $format_name) {
      $format = $this->getTextFormat($format_name);
      $this->assertIdentical($format->get('name'), $format_name);
    }

    $settings = $this->getFilterSettings('all');
    $this->assertEqual($settings['antisearch_filter_email'], TRUE);
    $this->assertEqual($settings['antisearch_filter_strike'], TRUE);
    $this->assertEqual($settings['antisearch_filter_bracket'], TRUE);
    $this->assertEqual($settings['antisearch_filter_show_title'], TRUE);

    $settings = $this->getFilterSettings('bracket');
    $this->assertEqual($settings['antisearch_filter_email'], FALSE);
    $this->assertEqual($settings['antisearch_filter_strike'], FALSE);
    $this->assertEqual($settings['antisearch_filter_bracket'], TRUE);
    $this->assertEqual($settings['antisearch_filter_show_title'], FALSE);

    $settings = $this->getFilterSettings('email');
    $this->assertEqual($settings['antisearch_filter_email'], TRUE);
    $this->assertEqual($settings['antisearch_filter_strike'], FALSE);
    $this->assertEqual($settings['antisearch_filter_bracket'], FALSE);
    $this->assertEqual($settings['antisearch_filter_show_title'], FALSE);

    $settings = $this->getFilterSettings('none');
    $this->assertEqual($settings['antisearch_filter_email'], FALSE);
    $this->assertEqual($settings['antisearch_filter_strike'], FALSE);
    $this->assertEqual($settings['antisearch_filter_bracket'], FALSE);
    $this->assertEqual($settings['antisearch_filter_show_title'], FALSE);

    $settings = $this->getFilterSettings('show_title');
    $this->assertEqual($settings['antisearch_filter_email'], FALSE);
    $this->assertEqual($settings['antisearch_filter_strike'], FALSE);
    $this->assertEqual($settings['antisearch_filter_bracket'], FALSE);
    $this->assertEqual($settings['antisearch_filter_show_title'], TRUE);

    $settings = $this->getFilterSettings('strike');
    $this->assertEqual($settings['antisearch_filter_email'], FALSE);
    $this->assertEqual($settings['antisearch_filter_strike'], TRUE);
    $this->assertEqual($settings['antisearch_filter_bracket'], FALSE);
    $this->assertEqual($settings['antisearch_filter_show_title'], FALSE);
  }

}
