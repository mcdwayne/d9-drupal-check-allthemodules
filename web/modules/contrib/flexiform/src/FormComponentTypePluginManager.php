<?php

namespace Drupal\flexiform;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a Flexiform form Entity Plugin Manager.
 */
class FormComponentTypePluginManager extends DefaultPluginManager {

  /**
   * Constructs a FormComponentTypePluginManager object.
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
    parent::__construct('Plugin/FormComponentType', $namespaces, $module_handler, 'Drupal\flexiform\FormComponent\FormComponentTypeInterface', 'Drupal\flexiform\Annotation\FormComponentType');
    $this->alterInfo('flexiform_form_compoent_type');
    $this->setCacheBackend($cache_backend, 'flexiform_form_component_type_plugins');
  }

}
