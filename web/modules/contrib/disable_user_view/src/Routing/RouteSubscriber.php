<?php

namespace Drupal\disable_user_view\Routing;

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
    if ($route = $collection->get('user.page')) {
      $route->setDefault('_controller', '\Drupal\disable_user_view\Controller\UserEditController::userPage');
    }
    if ($route = $collection->get('entity.user.canonical')) {
      $route->setDefault('_controller', '\Drupal\disable_user_view\Controller\UserEditController::userPage');
      $route->setRequirement('_access', 'FALSE');
    }
  }

}