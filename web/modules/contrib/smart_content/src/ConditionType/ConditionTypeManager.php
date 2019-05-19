<?php

namespace Drupal\smart_content\ConditionType;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides the Smart condition type plugin manager.
 */
class ConditionTypeManager extends DefaultPluginManager {

  public $plugin_form_exists;

  /**
   * Constructor for ConditionTypeManager objects.
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
    parent::__construct('Plugin/smart_content/ConditionType', $namespaces, $module_handler, 'Drupal\smart_content\ConditionType\ConditionTypeInterface', 'Drupal\smart_content\Annotation\SmartConditionType');

    $this->alterInfo('smart_content_smart_condition_type_info');
    $this->setCacheBackend($cache_backend, 'smart_content_smart_condition_type_plugins');
  }

  public function isPluginForm($condition_type) {
    if (!isset($this->plugin_form_exists[$condition_type])) {
      $this->plugin_form_exists[$condition_type] = $this->createInstance($condition_type) instanceof PluginFormInterface;
    }
    return $this->plugin_form_exists[$condition_type];
  }

}
