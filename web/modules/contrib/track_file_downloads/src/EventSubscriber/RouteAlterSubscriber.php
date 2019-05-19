<?php

namespace Drupal\track_file_downloads\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Routing\RouteBuildEvent;

/**
 * Route alter subscriber.
 */
class RouteAlterSubscriber implements EventSubscriberInterface {

  /**
   * Alter the system file routes so we can track downloads.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The event to process.
   */
  public function onRoutingAlter(RouteBuildEvent $event) {
    foreach ($event->getRouteCollection() as $route_name => $route) {
      if ($route_name === 'system.private_file_download' || $route_name === 'system.files') {
        $route->setDefault('_controller', 'Drupal\track_file_downloads\Controller\TrackingFileDownloadController::download');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER][] = ['onRoutingAlter'];
    return $events;
  }

}
