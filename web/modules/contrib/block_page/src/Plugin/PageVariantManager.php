<?php

/**
 * @file
 * Contains \Drupal\block_page\Plugin\PageVariantManager.
 */

namespace Drupal\block_page\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery of page variant plugins.
 */
class PageVariantManager extends DefaultPluginManager {

  /**
   * Constructs a new PageVariantManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/PageVariant', $namespaces, $module_handler, 'Drupal\block_page\Annotation\PageVariant');

    $this->setCacheBackend($cache_backend, $language_manager, 'page_variant_plugins');
    // @todo Set an alter hook.
  }

}
