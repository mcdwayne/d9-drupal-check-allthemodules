<?php

/**
 * @file
 * Contains Drupal\config_db\EventSubscriber\ConfigDbSubscriber.
 */

namespace Drupal\config_db\EventSubscriber;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Routing\RouteBuildEvent;

/**
 * ConfigDb subscriber for controller requests.
 */
class ConfigDbSubscriber implements EventSubscriberInterface {

  /**
   * Override the config export route.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The event to process.
   */
  public function onRoutingRouteAlterOverrideConfigExport(RouteBuildEvent $event) {
    $routeCollection = $event->getRouteCollection();
    if ($route = $routeCollection->get('config_export_download')) {
      $defaults = array('_content' => '\Drupal\config_db\Controller\ConfigExportController::downloadExport');
      $requirements = array('_permission' => 'export configuration');
      $routeCollection->remove('config_export_download');
      $routeCollection->add('config_export_download', new Route($route->getPath(), $defaults, $requirements));
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER][] = array('onRoutingRouteAlterOverrideConfigExport', 10);
    return $events;
  }
}

