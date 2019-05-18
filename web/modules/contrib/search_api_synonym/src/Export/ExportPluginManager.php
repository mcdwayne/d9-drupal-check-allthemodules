<?php

namespace Drupal\search_api_synonym\Export;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Base class for search api synonym export plugin managers.
 *
 * @ingroup plugin_api
 */
class ExportPluginManager extends DefaultPluginManager {

  /**
   * Active plugin id
   *
   * @var string
   */
  protected $pluginId;

  /**
   * Export options.
   *
   * @var array
   */
  protected $exportOptions;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/search_api_synonym/export', $namespaces, $module_handler, 'Drupal\search_api_synonym\Export\ExportPluginInterface', 'Drupal\search_api_synonym\Annotation\SearchApiSynonymExport');
    $this->alterInfo('search_api_synonym_export_info');
    $this->setCacheBackend($cache_backend, 'search_api_synonym_export_info_plugins');
  }

  /**
   * Set active plugin.
   *
   * @param string $plugin_id
   *   The active plugin.
   */
  public function setPluginId($plugin_id) {
    $this->pluginId = $plugin_id;
  }

  /**
   * Get active plugin.
   *
   * @return string
   *   The active plugin.
   */
  public function getPluginId() {
    return $this->pluginId;
  }

  /**
   * Set export options.
   *
   * @param array $export_options
   *   Array with export options
   */
  public function setExportOptions(array $export_options) {
    $this->exportOptions = $export_options;
  }

  /**
   * Get export options.
   *
   * @return array
   *   Array with export options
   */
  public function getExportOptions() {
    return $this->exportOptions;
  }

  /**
   * Get single export option.
   *
   * @param string $key
   *   Option key
   *
   * @return string
   *   Option value
   */
  public function getExportOption($key) {
    return isset($this->exportOptions[$key]) ? $this->exportOptions[$key] : '';
  }

  /**
   * Gets a list of available export plugins.
   *
   * @return array
   *   An array with the plugin names as keys and the descriptions as values.
   */
  public function getAvailableExportPlugins() {
    // Use plugin system to get list of available export plugins.
    $plugins = $this->getDefinitions();

    $output = [];
    foreach ($plugins as $id => $definition) {
      $output[$id] = $definition;
    }

    return $output;
  }

  /**
   * Validate that a specific export plugin exists.
   *
   * @param string $plugin
   *   The plugin machine name.
   *
   * @return boolean
   *   TRUE if the plugin exists.
   */
  public function validatePlugin($plugin) {
    if ($this->getDefinition($plugin, FALSE)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Execute the synonym export.
   *
   * @return mixed
   *   Export result
   */
  public function executeExport() {
    // Export plugin instance
    $instance = $this->createInstance($this->getPluginId(), []);

    // Get synonyms data matching the options.
    $synonyms = $this->getSynonymsData();

    // We only export if full export or if their is new synonyms.
    if (!($this->getExportOption('incremental') && empty($synonyms))) {
      // Get data in the plugin instance format
      $data = $instance->getFormattedSynonyms($synonyms);

      return $this->saveSynonymsFile($data);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Get synonyms  matching the export options.
   *
   * @return array
   *   Array with synonyms
   */
  private function getSynonymsData() {
    // Create the db query.
    $query = \Drupal::database()->select('search_api_synonym', 's');
    $query->fields('s', ['sid', 'type', 'word', 'synonyms']);
    $query->condition('s.status', 1);
    $query->condition('s.langcode', $this->getExportOption('langcode'));
    $query->orderBy('s.word');

    // Add type condition if it is set and different from all.
    $type = $this->getExportOption('type');
    if ($type && $type != 'all') {
      $query->condition('s.type', $type);
    }

    // Add filter condition if it is set and different from all.
    $filter = $this->getExportOption('filter');
    if ($filter && $filter != 'all') {
      switch ($filter) {
        case 'nospace':
          $query->condition('s.word', '% %', 'NOT LIKE');
          $query->condition('s.synonyms', '% %', 'NOT LIKE');
          break;
        case 'onlyspace':
          $group = $query->orConditionGroup()
            ->condition('s.word', '% %', 'LIKE')
            ->condition('s.synonyms', '% %', 'LIKE');
          $query = $query->condition($group);
          break;
      }
    }

    // Add changed condition if incremental option is set.
    if ($incremental = $this->getExportOption('incremental')) {
      $query->condition('s.changed', $incremental, '>=');
    }

    // Fetch the result.
    return $query->execute()->fetchAllAssoc('sid');
  }

  /**
   * Save synonyms data to a file.
   *
   * @param string $data
   *   String with the synonyms data being written to a file.
   *
   * @return string
   *   Return path to the saved synonyms file.
   */
  private function saveSynonymsFile($data) {
    if ($file = $this->getExportOption('file')) {
      $filename = $file;
    }
    else {
      $filename = $this->generateFileName();
    }

    // Create folder if it does not exist.
    $folder = 'public://synonyms';
    file_prepare_directory($folder, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);

    // Save file and return result.
    $path = $folder . '/'. $filename;
    return file_unmanaged_save_data($data, $path, FILE_EXISTS_REPLACE);
  }

  /**
   * Generate an export file name based on export options.
   *
   * @return string
   *   The generated file name.
   */
  private function generateFileName() {
    $options = $this->getExportOptions();

    // Add benning of file name
    $name[] = 'synonyms';

    // Add language code as the first part of the file name.
    $name[] = "lang_{$options['langcode']}";

    // Add type option to file name
    if (!empty($options['type'])) {
      $name[] = "type_{$options['type']}";
    }

    // Add filter option to file name
    if (!empty($options['filter'])) {
      $name[] = "filter_{$options['filter']}";
    }

    // Implode the name parts.
    return implode('__', $name) . '.txt';
  }

}
