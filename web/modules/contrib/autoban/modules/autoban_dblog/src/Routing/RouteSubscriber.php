<?php

/**
 * @file
 * Contains \Drupal\autoban_dblog\Routing\RouteSubscriber.
 */

namespace Drupal\autoban_dblog\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RouteSubscriber extends RouteSubscriberBase {

  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER] = [
      'onAlterRoutes',
      -176,
    ];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Change controller of '/admin/reports/dblog'.
    if ($route = $collection->get('dblog.overview')) {
      $route->setDefault('_controller','\Drupal\autoban_dblog\Controller\AutobanDbLogController::overview');
      $route->setDefault('_title', t('Recent log messages') . ' (autoban)');
    }
  }

}
