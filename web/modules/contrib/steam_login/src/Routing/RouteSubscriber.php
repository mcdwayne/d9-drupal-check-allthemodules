<?php

namespace Drupal\steam_login\Routing;

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
    $user_routes = [
      $collection->get('entity.user.canonical'),
      $collection->get('entity.user.edit_form'),
    ];

    foreach ($user_routes as $route) {
      $route->setDefault('_title_callback', 'Drupal\steam_login\UserAlter::alterUserTitle');
    }
  }

}
