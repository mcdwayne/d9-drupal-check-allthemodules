<?php

namespace Drupal\hp;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of hp form strategy plugins.
 */
class FormStrategyManager extends DefaultPluginManager {

  /**
   * Constructs a new FormStrategyManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/hp', $namespaces, $module_handler, 'Drupal\hp\Plugin\hp\FormStrategyInterface', 'Drupal\hp\Annotation\HpFormStrategy');

    $this->alterInfo('hp_form_strategy_info');
    $this->setCacheBackend($cache_backend, 'hp_form_strategy_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The hp form strategy %s must define the %s property.', $plugin_id, $required_property));
      }
    }
  }

}
