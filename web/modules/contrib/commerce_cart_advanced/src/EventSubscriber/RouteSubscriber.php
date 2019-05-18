<?php

namespace Drupal\commerce_cart_advanced\EventSubscriber;

use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Routing\RouteBuildEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event listener for altering the controller for commerce_order.
 */
class RouteSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      RoutingEvents::ALTER => ['onAlterRoutes', -10],
    ];
    return $events;
  }

  /**
   * Alters the cart controller to use a custom function.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route alter event.
   */
  public function onAlterRoutes(RouteBuildEvent $event) {
    $route = $event->getRouteCollection()->get('commerce_cart.page');
    if (!$route) {
      return;
    }

    $route->setDefault(
      '_controller',
      '\Drupal\commerce_cart_advanced\Controller\CartController::cartPage'
    );
  }

}
