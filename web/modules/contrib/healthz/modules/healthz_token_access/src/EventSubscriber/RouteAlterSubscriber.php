<?php

namespace Drupal\healthz_token_access\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alter the healthz route to add our own access control.
 */
class RouteAlterSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('healthz.check')) {
      $route->setRequirements([
        '_healthz_token_access' => 'true',
      ]);
    }
  }

}
