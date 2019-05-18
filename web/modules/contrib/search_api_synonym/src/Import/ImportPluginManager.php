<?php

namespace Drupal\search_api_synonym\Import;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Base class for search api synonym import plugin managers.
 *
 * @ingroup plugin_api
 */
class ImportPluginManager extends DefaultPluginManager {

  /**
   * Active plugin id
   *
   * @var string
   */
  protected $pluginId;

  /**
   * Import options.
   *
   * @var array
   */
  protected $importOptions;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/search_api_synonym/import', $namespaces, $module_handler, 'Drupal\search_api_synonym\Import\ImportPluginInterface', 'Drupal\search_api_synonym\Annotation\SearchApiSynonymImport');
    $this->alterInfo('search_api_synonym_import_info');
    $this->setCacheBackend($cache_backend, 'search_api_synonym_import_info_plugins');
  }

  /**
   * Set active plugin
   *
   * @param string $plugin_id
   *   The active plugin.
   */
  public function setPluginId($plugin_id) {
    $this->pluginId = $plugin_id;
  }

  /**
   * Get active plugin
   *
   * @return string
   *   The active plugin.
   */
  public function getPluginId() {
    return $this->pluginId;
  }

  /**
   * Gets a list of available import plugins.
   *
   * @return array
   *   An array with the plugin names as keys and the descriptions as values.
   */
  public function getAvailableImportPlugins() {
    // Use plugin system to get list of available import plugins.
    $plugins = $this->getDefinitions();

    $output = [];
    foreach ($plugins as $id => $definition) {
      $output[$id] = $definition;
    }

    return $output;
  }

  /**
   * Validate that a specific import plugin exists.
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

}
