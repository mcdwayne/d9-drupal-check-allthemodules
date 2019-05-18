<?php

/**
 * @file
 * Contains \Drupal\fasttoggle\FasttoggleObjectPluginManager.
 */

namespace Drupal\fasttoggle;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages Fasttoggle Manged Object plugins.
 *
 * As well as this class definition, we need to declare our plugin manager class
 * as a service, in the plugin_type_example.services.yml file.
 */
class SettingObjectPluginManager extends DefaultPluginManager {

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
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $subdir = 'Plugin/SettingObject';
    $plugin_interface = 'Drupal\fasttoggle\Plugin\SettingObject\SettingObjectInterface';
    $plugin_definition_annotation_name = 'Drupal\Component\Annotation\Plugin';

    parent::__construct($subdir, $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);

    $this->alterInfo('fasttoggle_setting_object_info');
    $this->setCacheBackend($cache_backend, 'fasttoggle_setting_object_info');
  }

}
