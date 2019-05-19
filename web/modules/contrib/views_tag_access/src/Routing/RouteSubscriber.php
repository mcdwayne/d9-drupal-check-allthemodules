<?php

namespace Drupal\views_tag_access\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Override the permissions on views listings.
    if ($route = $collection->get('entity.view.collection')) {
      $requirements = $route->getRequirements();
      unset($requirements['_permission']);
      $requirements['_views_tag_access_check'] = 'TRUE';
      $route->setRequirements($requirements);
    }
  }

}
