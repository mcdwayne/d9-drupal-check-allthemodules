<?php

namespace Drupal\empty_fields;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a plugin manager for empty fields.
 *
 * @see \Drupal\empty_fields\Annotation\EmptyField
 * @see \Drupal\empty_fields\EmptyFieldPluginBase
 * @see \Drupal\empty_fields\EmptyFieldPluginInterface
 * @see plugin_api
 */
class EmptyFieldsPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new EmptyFieldsPluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/EmptyFields', $namespaces, $module_handler, 'Drupal\empty_fields\EmptyFieldPluginInterface', 'Drupal\empty_fields\Annotation\EmptyField');

    //$this->setCacheBackend($cache_backend, 'empty_fields');
  }

}
