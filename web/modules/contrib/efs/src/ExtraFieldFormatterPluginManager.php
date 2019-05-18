<?php

namespace Drupal\efs;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Manages Extra Field formatter plugins.
 *
 * @package Drupal\efs\Plugin
 */
class ExtraFieldFormatterPluginManager extends DefaultPluginManager {

  /**
   * Constructor for ExtraFieldDisplayManager objects.
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

    parent::__construct('Plugin/efs/Formatter', $namespaces, $module_handler, 'Drupal\efs\ExtraFieldFormatterPluginInterface', 'Drupal\efs\Annotation\ExtraFieldFormatter');

    $this->alterInfo('extra_field_formatter_info');
    $this->setCacheBackend($cache_backend, 'extra_field_formatter_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    // Add the module or theme path to the 'path'.
    if ($this->moduleHandler->moduleExists($definition['provider'])) {
      $definition['provider_type'] = 'module';
      $definition['definition_path'] = $this->moduleHandler->getModule($definition['provider'])
        ->getPath();
    }
    else {
      $definition['definition_path'] = '';
    }
  }

}
