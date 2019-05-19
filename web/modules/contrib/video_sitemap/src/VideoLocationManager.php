<?php

namespace Drupal\video_sitemap;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Gathers the provider plugins.
 */
class VideoLocationManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/video_sitemap/VideoLocation', $namespaces, $module_handler, 'Drupal\video_sitemap\VideoLocationPluginInterface', 'Drupal\video_sitemap\Annotation\VideoLocation');
    $this->alterInfo('video_sitemap_video_location_info');
    $this->setCacheBackend($cache_backend, 'video_sitemap_video_location_plugins');
  }

}
