<?php

namespace Drupal\aegir_site_subscriptions\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Aegir site subscription provider plugin manager.
 */
class SubscriptionProviderManager extends DefaultPluginManager {

  /**
   * Constructs a new SubscriptionProviderManager object.
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
    parent::__construct(
      'Plugin/SubscriptionProvider',
      $namespaces,
      $module_handler,
      'Drupal\aegir_site_subscriptions\Plugin\SubscriptionProviderInterface',
      'Drupal\aegir_site_subscriptions\Annotation\SubscriptionProvider'
    );

    $this->alterInfo('aegir_site_subscriptions_aegir_site_subscription_provider_info');
    $this->setCacheBackend($cache_backend, 'aegir_site_subscriptions_aegir_site_subscription_provider_plugins');
  }

}
