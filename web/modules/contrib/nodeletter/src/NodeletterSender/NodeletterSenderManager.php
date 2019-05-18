<?php

/**
 * @file
 * Contains \Drupal\nodeletter\NodeletterSender\NodeletterSenderManager.
 */

namespace Drupal\nodeletter\NodeletterSender;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;



/**
 * Provides the plugin manager to handle NodeletterSender plugins.
 *
 * @see \Drupal\nodeletter\NodeletterSender\NodeletterSenderPluginInterface
 * @see plugin_api
 */
class NodeletterSenderManager extends DefaultPluginManager {

  /**
   * Constructs a NewsletterSender plugin manager.
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
      'Plugin/NodeletterSender',
      $namespaces,
      $module_handler,
      'Drupal\nodeletter\NodeletterSender\NodeletterSenderPluginInterface',
      '\Drupal\Component\Annotation\Plugin'
    );
//    $this->alterInfo('newsletter_sender_info');
    $this->setCacheBackend($cache_backend, 'nodeletter_sender_plugins');
    $this->factory = new DefaultFactory($this->getDiscovery());
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\nodeletter\NodeletterSender\NodeletterSenderPluginInterface
   */
  public function createInstance($plugin_id, array $configuration = []) {
    return parent::createInstance($plugin_id, $configuration);
  }

}
