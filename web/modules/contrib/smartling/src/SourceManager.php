<?php

/**
 * @file
 * Contains \Drupal\smartling\SourceManager.
 */

namespace Drupal\smartling;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\smartling\Plugin\smartling\Source\ContentEntitySource;
use Drupal\smartling\Plugin\smartling\Source\ConfigEntitySource;

/**
 * A plugin manager for smartling source plugins.
 */
class SourceManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/smartling/Source', $namespaces, $module_handler, 'Drupal\smartling\SourcePluginInterface', 'Drupal\smartling\Annotation\SourcePlugin');
    $this->alterInfo('smartling_source_plugin_info');
    $this->setCacheBackend($cache_backend, 'smartling_source_plugin');
  }

  public function createInstance($plugin_id, array $configuration = array()) {
    $container = \Drupal::getContainer();

    if ($plugin_id == 'configuration') {
      return ConfigEntitySource::create($container, $configuration, $plugin_id, []);
    }
    return ContentEntitySource::create($container, $configuration, $plugin_id, []);
  }
}
