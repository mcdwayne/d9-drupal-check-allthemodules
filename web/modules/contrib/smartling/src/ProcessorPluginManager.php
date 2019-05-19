<?php

/**
 * @file
 * Contains \Drupal\smartling\ProcessorPluginManager.
 */

namespace Drupal\smartling;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * A plugin manager for smartling processor plugins.
 */
class ProcessorPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/smartling/ProcessorPlugin', $namespaces, $module_handler, 'Drupal\smartling\ProcessorPluginInterface', 'Drupal\smartling\Annotation\ProcessorPlugin');
    $this->alterInfo('smartling_processor_plugin_info');
    $this->setCacheBackend($cache_backend, 'smartling_processor_plugin');
  }

}
