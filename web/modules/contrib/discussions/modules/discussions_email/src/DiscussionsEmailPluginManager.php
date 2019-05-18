<?php

namespace Drupal\discussions_email;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a discussions email plugin manager.
 *
 * @see \Drupal\discussions_email\Plugin\DiscussionsEmailPluginInterface
 * @see plugin_api
 */
class DiscussionsEmailPluginManager extends DefaultPluginManager {

  /**
   * Constructs a DiscussionsEmailPluginManager object.
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
      'Plugin/DiscussionsEmailPlugin',
      $namespaces,
      $module_handler,
      'Drupal\discussions_email\Plugin\DiscussionsEmailPluginInterface',
      'Drupal\discussions_email\Annotation\DiscussionsEmailPlugin'
    );
    $this->alterInfo('discussions_email_info');
    $this->setCacheBackend($cache_backend, 'discussions_email_plugins');
    $this->factory = new DefaultFactory($this->getDiscovery());
  }

}
