<?php

namespace Drupal\flexiform;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Context\ContextAwarePluginManagerInterface;
use Drupal\Core\Plugin\Context\ContextAwarePluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a Form Element Plugin Manager.
 */
class FormElementPluginManager extends DefaultPluginManager implements ContextAwarePluginManagerInterface {
  use ContextAwarePluginManagerTrait;

  /**
   * Constructs a FormElementPluginManager object.
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
    parent::__construct('Plugin/FormElement', $namespaces, $module_handler, 'Drupal\flexiform\FormElement\FormElementInterface', 'Drupal\flexiform\Annotation\FormElement');
    $this->alterInfo('flexiform_form_element');
    $this->setCacheBackend($cache_backend, 'flexiform_form_element');
  }

}
