<?php

namespace Drupal\access_filter\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages access filter plugins.
 *
 * @see \Drupal\access_filter\Annotation\AccessFilterCondition
 * @see \Drupal\access_filter\Annotation\AccessFilterRule
 * @see \Drupal\access_filter\Plugin\ConditionInterface
 * @see \Drupal\access_filter\Plugin\RuleInterface
 * @see plugin_api
 */
class AccessFilterPluginManager extends DefaultPluginManager implements AccessFilterPluginManagerInterface {

  /**
   * Constructs a new AccessFilterPluginManager object.
   *
   * @param string $type
   *   The plugin type, for example fetcher.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct($type, \Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $type_annotations = [
      'condition' => 'Drupal\access_filter\Annotation\AccessFilterCondition',
      'rule' => 'Drupal\access_filter\Annotation\AccessFilterRule',
    ];
    $plugin_interfaces = [
      'condition' => 'Drupal\access_filter\Plugin\ConditionInterface',
      'rule' => 'Drupal\access_filter\Plugin\RuleInterface',
    ];

    parent::__construct('Plugin/AccessFilter/' . ucwords($type, '_'), $namespaces, $module_handler, $plugin_interfaces[$type], $type_annotations[$type]);
    $this->setCacheBackend($cache_backend, 'access_filter_' . $type . '_plugins');
  }

}
