<?php

namespace Drupal\chatbot_api_entities\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Push handler plugin manager.
 */
class PushHandlerManager extends DefaultPluginManager {

  /**
   * Constructs a new PushHandlerManager object.
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
    parent::__construct('Plugin/ChatbotApiEntities/PushHandler', $namespaces, $module_handler, 'Drupal\chatbot_api_entities\Plugin\PushHandlerInterface', 'Drupal\chatbot_api_entities\Annotation\PushHandler');

    $this->alterInfo('chatbot_api_entities_chatbot_api_entities_push_handler_info');
    $this->setCacheBackend($cache_backend, 'chatbot_api_entities_chatbot_api_entities_push_handler_plugins');
  }

}
