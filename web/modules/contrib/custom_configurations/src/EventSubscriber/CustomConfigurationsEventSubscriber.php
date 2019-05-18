<?php

namespace Drupal\custom_configurations\EventSubscriber;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\ProxyClass\Routing\RouteBuilder;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber CustomConfigurationsEventSubscriber.
 */
class CustomConfigurationsEventSubscriber implements EventSubscriberInterface {

  /**
   * The CacheBackendInterface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The route builder.
   *
   * @var \Drupal\Core\ProxyClass\Routing\RouteBuilder
   */
  protected $routerBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(CacheBackendInterface $cache, RouteBuilder $router_builder) {
    $this->cache = $cache;
    $this->routerBuilder = $router_builder;
  }

  /**
   * Triggered on page request.
   */
  public function onRequest() {
    // Forcibly rebuild routes if "drush cache rebuild" was triggered.
    if (empty($this->cache->get('custom_configurations_routes_rebuild')->data)) {
      $this->cache->set('custom_configurations_routes_rebuild', TRUE);
      $this->routerBuilder->rebuild();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest'];
    return $events;
  }

}
