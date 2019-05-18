<?php

namespace Drupal\interface_string_stats\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class StringStatsRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('locale.translate_page')) {
      $route->setDefaults([
        '_controller' => '\Drupal\interface_string_stats\Controller\StringStatsController::translatePage',
        '_title' => 'User interface translation',
      ]);
    }
  }

}
