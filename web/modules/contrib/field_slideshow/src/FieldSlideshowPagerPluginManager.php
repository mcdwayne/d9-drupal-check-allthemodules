<?php

namespace Drupal\field_slideshow;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * FieldSlideshowPager plugin manager.
 */
class FieldSlideshowPagerPluginManager extends DefaultPluginManager {

  /**
   * Constructs FieldSlideshowPagerPluginManager object.
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
      'Plugin/FieldSlideshowPager',
      $namespaces,
      $module_handler,
      'Drupal\field_slideshow\FieldSlideshowPagerInterface',
      'Drupal\field_slideshow\Annotation\FieldSlideshowPager'
    );
    $this->alterInfo('field_slideshow_pager_info');
    $this->setCacheBackend($cache_backend, 'field_slideshow_pager_plugins');
  }

}
