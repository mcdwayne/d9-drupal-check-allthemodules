<?php

namespace Drupal\rel_content;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\plugin_type_example\Annotation\Sandwich;
use Drupal\rel_content\Annotation\RelatedContent;

/**
 * A plugin manager for related content plugins.
 */
class RelatedContentPluginManager extends DefaultPluginManager {

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
    $plugin_interface = RelatedContentInterface::class;

    // The name of the annotation class that contains the plugin definition.
    $plugin_definition_annotation_name = RelatedContent::class;

    parent::__construct('Plugin/RelatedContent', $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);

    $this->alterInfo('rel_content_info');
    $this->setCacheBackend($cache_backend, 'rel_content_info');
  }

}
