<?php

namespace Drupal\image_styles_mapping\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Base class for image styles mapping plugin managers.
 *
 * @ingroup plugin_api
 */
class ImageStylesMappingPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ImageStylesMapping', $namespaces, $module_handler, 'Drupal\image_styles_mapping\Plugin\ImageStylesMappingPluginInterface', 'Drupal\image_styles_mapping\Annotation\ImageStylesMapping');
    $this->alterInfo('image_styles_mapping_info');
    $this->setCacheBackend($cache_backend, 'image_styles_mapping_plugins');
  }

}
