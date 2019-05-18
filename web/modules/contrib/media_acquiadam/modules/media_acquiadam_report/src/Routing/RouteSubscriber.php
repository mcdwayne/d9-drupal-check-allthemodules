<?php

namespace Drupal\media_acquiadam_report\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Display asset listing as an admin page.
    if ($route = $collection->get('view.acquia_dam_reporting.asset_report')) {
      $route->setOption('_admin_route', TRUE);
    }
  }

}
