<?php

namespace Drupal\micro_simple_sitemap\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Add additional access control on micro_site type sitemap.
    if ($route = $collection->get('simple_sitemap.sitemap_variant')) {
      $route->addRequirements([
        '_custom_access' => '\Drupal\micro_simple_sitemap\Access\MicroSimpleSitemapAccess:access',
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', 101];

    return $events;
  }

}
