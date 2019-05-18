<?php

namespace Drupal\pach;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages access control handlers.
 *
 * @see hook_pach_handler_info_alter()
 * @see \Drupal\pach\Annotation\AccessControlHandler
 * @see \Drupal\pach\Plugin\AccessControlHandlerInterface
 * @see \Drupal\pach\Plugin\AccessControlHandlerBase
 * @see plugin_api
 */
class AccessControlHandlerManager extends DefaultPluginManager {

  /**
   * Constructs a AccessControlHandlerManager object.
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
    parent::__construct('Plugin/pach', $namespaces, $module_handler, 'Drupal\pach\Plugin\AccessControlHandlerInterface', 'Drupal\pach\Annotation\AccessControlHandler');
    $this->alterInfo('pach_handler_info');
    $this->setCacheBackend($cache_backend, 'pach_handlers');
  }

  /**
   * Get a list of all registered handler instances sorted by weight.
   *
   * @param string $entity_type
   *   Limit handlers to the given entity type.
   *
   * @return \Drupal\pach\Plugin\AccessControlHandlerInterface[]
   *   List of processor plugin instances, optionally limited to an entity type.
   */
  public function getHandlers($entity_type) {
    $instances = &drupal_static(__FUNCTION__, []);
    if (!empty($instances[$entity_type])) {
      return $instances[$entity_type];
    }

    $instances[$entity_type] = [];
    /* @var $handlers \Drupal\pach\Plugin\AccessControlHandlerInterface[] */
    $handlers = $this->limitHandlers($this->getDefinitions(), $entity_type);
    uasort($handlers, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    foreach ($handlers as $plugin_id => $handler) {
      // Execute the processor plugin.
      $instances[$entity_type][$plugin_id] = $this->createInstance($plugin_id, $handler);
    }

    return $instances[$entity_type];
  }

  /**
   * Reduce a list of access control handlers to a single entity type.
   *
   * @param \Drupal\pach\Plugin\AccessControlHandlerInterface[] $handlers
   *   List of access control handlers.
   * @param string $entity_type
   *   Name of entity type.
   *
   * @return \Drupal\pach\Plugin\AccessControlHandlerInterface[]
   *   List of access control handlers for the given entity type.
   */
  protected function limitHandlers(array $handlers, $entity_type) {
    return array_filter($handlers, function ($handler) use ($entity_type) {
      if (is_array($handler)) {
        return $entity_type === $handler['type'];
      }
      /* @var $handler \Drupal\pach\Plugin\AccessControlHandlerInterface */
      return $entity_type === $handler->getEntityType();
    });
  }

}
