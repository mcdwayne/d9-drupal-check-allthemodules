<?php
/**
 * @file
 * Contains \Drupal\dummyimage\DummyImageProviderManager
 */

namespace Drupal\dummyimage;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

class DummyImageProviderManager extends DefaultPluginManager {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ImageProvider', $namespaces, $module_handler, 'Drupal\dummyimage\DummyImageProviderInterface', 'Drupal\dummyimage\Annotation\ImageProvider');

    $this->alterInfo('dummyimage_provider_info');
    $this->setCacheBackend($cache_backend, 'dummyimage_providers');
  }
}