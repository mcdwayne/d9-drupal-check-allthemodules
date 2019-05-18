<?php

namespace Drupal\commerce_xero;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\TypedData\ComplexDataInterface;

/**
 * Commerce Xero Processor Plugin Manager.
 */
class CommerceXeroProcessorManager extends DefaultPluginManager {

  /**
   * CommerceXeroProcessorManager constructor.
   *
   * @param \Traversable $namespaces
   *   A list of namespaces to traverse.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The Drupal cache backend to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The Drupal module handler service.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $plugin_definition_annotation_name = '\Drupal\commerce_xero\Annotation\CommerceXeroProcessor';
    $plugin_interface = '\Drupal\commerce_xero\CommerceXeroProcessorPluginInterface';

    parent::__construct('Plugin/CommerceXero/processor', $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);

    $this->alterInfo('commerce_xero_processor_plugin_info');
    $this->setCacheBackend($cache_backend, 'commerce_xero_processor_plugins');
  }

  /**
   * Run all process plugins for a given execution state.
   *
   * @param \Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface $strategy
   *   The commerce_xero strategy entity.
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The comemrce payment.
   * @param \Drupal\Core\TypedData\ComplexDataInterface $data
   *   The xero data.
   * @param string $execution
   *   The execution state: immediate, process or send.
   *
   * @return bool
   *   Whether all of the process plugins succeeded or not.
   */
  public function process(CommerceXeroStrategyInterface $strategy, PaymentInterface $payment, ComplexDataInterface $data, $execution) {
    $success = TRUE;
    $plugins = $this->getStrategyPluginCollection($strategy, $execution);
    foreach ($plugins as $id => $processor) {
      /** @var \Drupal\commerce_xero\CommerceXeroProcessorPluginInterface $processor */
      $success = $processor->process($payment, $data);
    }
    $hook = 'commerce_xero_process_' . $execution;
    $context = [
      'payment' => $payment,
      'strategy' => $strategy,
      'success' => $success,
    ];
    $this->moduleHandler->alter($hook, $data, $context);
    $this->moduleHandler->alter('commerce_xero_process', $data, $context);
    return $success;
  }

  /**
   * Creates plugin instances from a strategy for a given execution state.
   *
   * It seems odd to return a plugin collection from the plugin manager itself,
   * but this saves the calling code knowing implementation details and logic
   * about sorting and filtering strategy plugins based on execution state
   * and/or data type.
   *
   * @param \Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface $strategy
   *   The commerce_xero strategy configuration entity.
   * @param string $execution
   *   The execution state. One of immediate, process or send. Defaults to all.
   *
   * @return \Drupal\Core\Plugin\DefaultLazyPluginCollection
   *   A collection of lazily-instantiated processor plugins.
   */
  public function getStrategyPluginCollection(CommerceXeroStrategyInterface $strategy, $execution = '') {
    $configurations = [];
    $strategy_plugins = $strategy->get('plugins');

    foreach ($strategy_plugins as $key => $strategy_plugin) {
      $plugin_id = $strategy_plugin['name'];
      $definition = $this->getDefinition($plugin_id, FALSE);

      if ($execution === '' || $definition['execution'] === $execution) {
        $configurations[$plugin_id] = [
          'id' => $plugin_id,
          'settings' => isset($strategy_plugin['settings']) ? $strategy_plugin['settings'] : [],
        ];
      }

    }

    return new DefaultLazyPluginCollection($this, $configurations);
  }

}
