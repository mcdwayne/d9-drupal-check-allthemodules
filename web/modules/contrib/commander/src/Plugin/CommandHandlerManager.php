<?php

namespace Drupal\commander\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Command handler plugin manager.
 */
class CommandHandlerManager extends DefaultPluginManager {

  /**
   * Constructs a new CommandHandlerManager object.
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
    parent::__construct('Plugin/CommandHandler', $namespaces, $module_handler, 'Drupal\commander\Plugin\CommandHandlerInterface', 'Drupal\commander\Annotation\CommandHandler');

    $this->alterInfo('commander_command_handler_info');
    $this->setCacheBackend($cache_backend, 'commander_command_handler_plugins');
  }

}
