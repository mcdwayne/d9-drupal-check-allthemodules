<?php

namespace Drupal\drush_help\Routing;

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
    // Change the controller for the help page.
    if ($route = $collection->get('help.page')) {
      $route->setDefault('_controller', '\Drupal\drush_help\Controller\HelpController::helpPage');
    }
  }

}
