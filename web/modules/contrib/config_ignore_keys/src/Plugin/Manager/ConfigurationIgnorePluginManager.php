<?php

namespace Drupal\config_ignore_keys\Plugin\Manager;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class ConfigurationIgnorePluginManager.
 *
 * @package Drupal\config_ignore_keys\Plugin\Manager
 */
class ConfigurationIgnorePluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ConfigIgnore', $namespaces, $module_handler,
      'Drupal\config_ignore_keys\Plugin\ConfigurationIgnorePluginInterface',
      'Drupal\config_ignore_keys\Annotation\ConfigurationIgnorePlugin');

    $this->alterInfo('config_ignore_keys_config_ignore_plugin_info');
    $this->setCacheBackend($cache_backend, 'config_ignore_keys_config_ignore_plugin_plugins');
  }

  /**
   * Gets the ignore definitions.
   *
   * @return array
   *   The ignore defitions.
   */
  public function getDefinitions() {
    $plugins = parent::getDefinitions();
    if (empty($plugins)) {
      $plugins = [];
    }

    return $plugins;
  }

}
