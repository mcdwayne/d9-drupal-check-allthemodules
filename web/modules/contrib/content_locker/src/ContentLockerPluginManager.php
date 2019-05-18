<?php

namespace Drupal\content_locker;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\content_locker\Annotation\ContentLocker;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * A plugin manager for content locker plugins.
 */
class ContentLockerPluginManager extends DefaultPluginManager {

  /**
   * Creates the discovery object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config interface.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    $dir = 'Plugin/ContentLocker';
    $plugin_interface = ContentLockerPluginInterface::class;
    $plugin_definition_annotation_name = ContentLocker::class;

    parent::__construct($dir, $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);

    $this->alterInfo('content_locker_info');

    $this->setCacheBackend($cache_backend, 'content_locker_info');

  }

}
