<?php

namespace Drupal\service_comment_count;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Comment service plugin manager.
 */
class CommentServiceManager extends DefaultPluginManager {

  /**
   * Constructs a new CommentServiceManager object.
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
    parent::__construct('Plugin/ServiceCommentCount/CommentService', $namespaces, $module_handler, 'Drupal\service_comment_count\CommentServiceInterface', 'Drupal\service_comment_count\Annotation\CommentService');

    $this->alterInfo('service_comment_count_service_info');
    $this->setCacheBackend($cache_backend, 'service_comment_count_service_plugins');
  }

  /**
   * Get all comment services.
   *
   * @return array
   *   The comment service plugins.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getServices() {
    $plugins = $this->getDefinitions();

    foreach ($plugins as $plugin_id => $plugin) {
      $instance = $this->createInstance($plugin_id);

      if ($instance instanceof CommentServiceInterface) {
        // Attach the class instance to the plugin definitions.
        $plugins[$plugin_id]['instance'] = $instance;
      }
      else {
        $plugins[$plugin_id] = [];
      }
    }

    return $plugins;
  }

}
