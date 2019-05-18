<?php

namespace Drupal\commerce_xero;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Commerce Xero Data Type Plugin Manager.
 */
class CommerceXeroDataTypeManager extends DefaultPluginManager implements PluginManagerInterface {

  /**
   * Initialize method.
   *
   * @param \Traversable $namespaces
   *   A list of namespaces to traverse.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The Drupal cache backend to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The Drupal module handler service.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $plugin_definition_annotation_name = '\Drupal\commerce_xero\Annotation\CommerceXeroDataType';
    $plugin_interface = '\Drupal\commerce_xero\CommerceXeroDataTypePluginInterface';
    parent::__construct('Plugin/CommerceXero/type', $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);

    $this->alterInfo('commerce_xero_data_type_plugin_info');
    $this->setCacheBackend($cache_backend, 'commerce_xero_data_type_plugins');
  }

  /**
   * Creates Xero data for given payment and strategy based on xero type.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The Commerce Payment entity.
   * @param \Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface $strategy
   *   The Commerce Xero Strategy entity.
   *
   * @return \Drupal\Core\TypedData\ComplexDataInterface
   *   The xero data type with values from the payment and strategy.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function createData(PaymentInterface $payment, CommerceXeroStrategyInterface $strategy) {
    $plugin_id = $strategy->get('xero_type');
    /** @var \Drupal\commerce_xero\CommerceXeroDataTypePluginInterface $plugin */
    $definition = $this->getDefinition($plugin_id);
    $plugin = $this->createInstance($plugin_id, $definition);
    $data = $plugin->make($payment, $strategy);

    // Allows some custom modules ability to alter data type without
    // implementing a commerce_xero processing plugin.
    $this->moduleHandler->alter(
      'commerce_xero_data',
      $data,
      $payment,
      $strategy);

    return $data;
  }

}
