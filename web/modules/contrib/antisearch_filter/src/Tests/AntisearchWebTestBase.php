<?php

namespace Drupal\antisearch_filter\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Config\FileStorage;

/**
 * Base class for the antisearch filter tests.
 *
 * Some helper methods.
 */
abstract class AntisearchWebTestBase extends WebTestBase {

  private $defaults = [
    'antisearch_filter_email' => TRUE,
    'antisearch_filter_strike' => TRUE,
    'antisearch_filter_bracket' => TRUE,
    'antisearch_filter_show_title' => TRUE,
  ];

  /**
   * Create a new text format.
   *
   * Create a new text format with an enabled antisearch filter
   * programmatically.
   *
   * @param string $name
   *   The machine name of the new text format.
   */
  protected function createTextFormatProgrammatically($name) {
    $config_path = drupal_get_path('module', 'antisearch_filter') . '/src/Tests/configs';
    $source = new FileStorage($config_path);
    $config_storage = \Drupal::service('config.storage');
    $config_storage->write('filter.format.' . $name, $source->read($name));
  }

  /**
   * Get the object of a text format.
   *
   * @param string $format_name
   *   The machine name of the new text format.
   */
  protected function getTextFormat($format_name) {
    filter_formats_reset();
    $formats = filter_formats();
    return $formats[$format_name];
  }

  /**
   * Get the settings of a text format.
   *
   * @param string $format_name
   *   The machine name of the new text format.
   */
  protected function getFilterSettings($format_name) {
    $format = $this->getTextFormat($format_name);
    $filters = $format->filters()->getAll();
    $antisearch_filter = $filters['filter_antisearch'];
    return $antisearch_filter->settings;
  }

  /**
   * Create a new text format.
   *
   * Create a new text format with an enabled antisearch filter using the web
   * functions provided by simpletest.
   *
   * @param string $name
   *   The machine name of the new text format.
   * @param array $settings
   *   The antisearch filter settings.
   */
  protected function createTextFormatWeb($name, array $settings = []) {
    $settings += $this->defaults;
    $edit = [
      'format' => $name,
      'name' => $name,
      'roles[anonymous]' => 1,
      'roles[authenticated]' => 1,
    ];
    $edit['filters[filter_antisearch][status]'] = 1;
    foreach ($settings as $key => $value) {
      $edit['filters[filter_antisearch][settings][' . $key . ']'] = $value;
    }
    $this->drupalPostForm('admin/config/content/formats/add', $edit, t('Save configuration'));
    filter_formats_reset();
    $formats = filter_formats();
    return $formats[$name];
  }

}
