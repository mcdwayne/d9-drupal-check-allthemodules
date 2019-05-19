<?php

namespace Drupal\trending_images;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/** Manages discovery and instantiation of entity trait plugins
 *
 */

class TrendingImagesManager extends DefaultPluginManager implements TrendingImagesManagerInterface {

  /**
   * Constructs a TrendingImagesManager object.
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
    parent::__construct(
      'Plugin/TrendingSocialChannel',
      $namespaces,
      $module_handler,
      'Drupal\trending_images\Plugin\TrendingSocialChannel\TrendingImagesInterface',
      'Drupal\trending_images\Annotation\TrendingImagesSocialChannel'
    );
    $this->alterInfo('trending_images_social_channel_info');
    $this->setCacheBackend($cache_backend, 'trending_images_social_channel_info_plugins');
  }


}
