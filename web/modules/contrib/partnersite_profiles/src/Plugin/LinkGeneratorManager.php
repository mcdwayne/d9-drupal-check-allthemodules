<?php

namespace Drupal\partnersite_profile\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Access Link generator plugin manager.
 */
class LinkGeneratorManager extends DefaultPluginManager {


  /**
   * Constructs a new LinkGeneratorManager object.
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
    parent::__construct('Plugin/LinkGenerator', $namespaces, $module_handler, 'Drupal\partnersite_profile\Plugin\LinkGeneratorInterface', 'Drupal\partnersite_profile\Annotation\LinkGenerator');

    $this->alterInfo('partnersite_profile_link_generator_info');
    $this->setCacheBackend($cache_backend, 'partnersite_profile_link_generator_plugins');
  }

}
