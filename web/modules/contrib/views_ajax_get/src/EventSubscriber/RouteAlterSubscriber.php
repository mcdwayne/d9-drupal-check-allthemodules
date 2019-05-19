<?php

namespace Drupal\views_ajax_get\EventSubscriber;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RouteAlterSubscriber implements EventSubscriberInterface {

  public function onRouteAlter(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    if ($route = $collection->get('views.ajax')) {
      $route->setDefault('_controller', '\Drupal\views_ajax_get\Controller\ViewsAjaxController::ajaxView');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER][] = ['onRouteAlter'];
    return $events;
  }

}
