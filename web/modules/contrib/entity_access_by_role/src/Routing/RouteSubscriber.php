<?php

namespace Drupal\entity_access_by_role\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * RouteSubscriber class.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Alters existing routes for a specific collection.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection for adding routes.
   */
  protected function alterRoutes(RouteCollection $collection) {

    // Add entity access check to taxonomy terms.
    if ($route = $collection->get('entity.taxonomy_term.canonical')) {

      $route->setRequirement('node', '\d+')
        ->setRequirement('_entity_access', "taxonomy_term.view");
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -200];
    return $events;
  }

}
