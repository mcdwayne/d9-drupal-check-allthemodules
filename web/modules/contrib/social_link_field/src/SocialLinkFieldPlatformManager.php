<?php

namespace Drupal\social_link_field;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Manager class for the platform plugins.
 */
class SocialLinkFieldPlatformManager extends DefaultPluginManager {

  /**
   * Constructs an SocialLinkFieldPlatformManager object.
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
    parent::__construct('Plugin/SocialLinkField/Platform', $namespaces, $module_handler, NULL, 'Drupal\social_link_field\Annotation\SocialLinkFieldPlatform');
  }

  /**
   * Get all platform plugins.
   *
   * @return array
   *   The platform plugins.
   */
  public function getPlatforms() {
    $plugins = $this->getDefinitions();
    return $plugins;
  }

}
