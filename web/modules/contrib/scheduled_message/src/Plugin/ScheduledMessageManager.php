<?php

namespace Drupal\scheduled_message\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Scheduled message plugin manager.
 */
class ScheduledMessageManager extends DefaultPluginManager {

  /**
   * Constructor for ScheduledMessageManager objects.
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
    parent::__construct('Plugin/ScheduledMessage', $namespaces, $module_handler, 'Drupal\scheduled_message\Plugin\ScheduledMessageInterface', 'Drupal\scheduled_message\Annotation\ScheduledMessage');

    $this->alterInfo('scheduled_message_info');
    $this->setCacheBackend($cache_backend, 'scheduled_message_plugins');
  }

}
