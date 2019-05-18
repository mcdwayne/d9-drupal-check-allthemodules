<?php

namespace Drupal\commerce_recurring_metered;

use Drupal\commerce_recurring_metered\Annotation\CommerceRecurringUsageType;
use Drupal\commerce_recurring_metered\Plugin\Commerce\UsageType\UsageTypeInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of usage type plugins.
 *
 * @see \Drupal\commerce_recurring\Annotation\CommerceRecurringUsageType
 * @see plugin_api
 */
class UsageTypeManager extends DefaultPluginManager {

  /**
   * Constructs a new UsageTypeManager object.
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
    parent::__construct(
      'Plugin/Commerce/UsageType',
      $namespaces,
      $module_handler,
      UsageTypeInterface::class,
      CommerceRecurringUsageType::class
    );

    $this->alterInfo('commerce_recurring_metered_usage_type_info');
    $this->setCacheBackend($cache_backend, 'commerce_recurring_metered_usage_type_plugins');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The recurring usage type %s must define the %s property.', $plugin_id, $required_property));
      }
    }
  }

}
