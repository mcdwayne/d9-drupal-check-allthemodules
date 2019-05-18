<?php

namespace Drupal\pki_ra\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.node.canonical')) {
      $route->setRequirement('_custom_access', '\Drupal\pki_ra\Controller\PKIRAController::access');
    }
  }

}
