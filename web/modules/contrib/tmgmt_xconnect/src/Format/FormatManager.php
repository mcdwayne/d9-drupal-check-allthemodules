<?php

/**
 * @file
 * Contains \Drupal\tmgmt_xconnect\Format\FormatManager.
 */

namespace Drupal\tmgmt_xconnect\Format;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * A plugin manager for file format plugins.
 */
class FormatManager extends DefaultPluginManager {

  /**
   * Array of instantiated source UI instances.
   *
   * @var array
   */
  protected $ui = array();

  protected $defaults = array(
    'ui' => '\Drupal\tmgmt\SourcePluginUiBase',
  );

  /**
   * Constructs a ConditionManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/tmgmt_xconnect/Format', $namespaces, $module_handler, '\Drupal\tmgmt_xconnect\Format\FormatInterface', 'Drupal\tmgmt_xconnect\Annotation\FormatPlugin');
    $this->alterInfo('tmgmt_xconnect_format_plugin_info');
    $this->setCacheBackend($cache_backend, 'tmgmt_xconnect_format_plugin');
  }

  /**
   * Returns a source plugin UI instance.
   *
   * @param string $plugin
   *   Name of the source plugin.
   *
   * @return \Drupal\tmgmt\SourcePluginUiInterface
   *   Instance a source plugin UI instance.
   */
  public function createUIInstance($plugin) {
    if (!isset($this->ui[$plugin])) {
      $definition = $this->getDefinition($plugin);
      $class = $definition['ui'];
      $this->ui[$plugin] = new $class(array(), $plugin, $definition);
    }
    return $this->ui[$plugin];
  }

  /**
   * Returns an array of format plugin labels.
   */
  public function getLabels() {
    $labels = array();
    foreach ($this->getDefinitions() as $id => $definition) {
      $labels[$id] = $definition['label'];
    }
    return $labels;
  }

}
