<?php

namespace Drupal\fico\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Field formatter condition plugin manager.
 */
class FieldFormatterConditionManager extends DefaultPluginManager {

  /**
   * Constructor for FieldFormatterConditionManager objects.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Field/FieldFormatter/Condition', $namespaces, $module_handler, 'Drupal\fico\Plugin\FieldFormatterConditionInterface', 'Drupal\fico\Annotation\FieldFormatterCondition');

    $this->alterInfo('fico_field_formatter_condition_info');
    $this->setCacheBackend($cache_backend, 'fico_field_formatter_condition_plugins');
  }

  /**
   * Get definied plugins.
   */
  public function getPlugins() {
    return $this->getDefinitions();
  }

}
