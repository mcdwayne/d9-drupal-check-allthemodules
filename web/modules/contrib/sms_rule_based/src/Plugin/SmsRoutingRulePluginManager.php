<?php

namespace Drupal\sms_rule_based\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages SMS routing rule types implemented using AnnotatedClassDiscovery.
 */
class SmsRoutingRulePluginManager extends DefaultPluginManager {

  /**
   * Creates a new SmsGatewayPluginManager instance.
   *
   * @param \Traversable $namespaces
   *   The namespaces to search for the gateway plugins.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler for calling module hooks.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/SmsRoutingRule', $namespaces, $module_handler, 'Drupal\sms_rule_based\Plugin\SmsRoutingRulePluginInterface', 'Drupal\sms_rule_based\Annotation\SmsRoutingRule');
    $this->setCacheBackend($cache_backend, 'sms_routing_rule');
    $this->alterInfo('sms_routing_rule');
  }

}
