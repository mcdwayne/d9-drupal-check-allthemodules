<?php

namespace Drupal\simple_pass_reset\Routing;

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
    // Alter user reset password page.
    $route = $collection->get('user.reset');
    if ($route) {
      $route->setDefaults([
        '_title' => 'Choose a new password',
        '_controller' => '\Drupal\simple_pass_reset\Controller\User::resetPass',
      ]);
      $route->setRequirement('_custom_access', '\Drupal\simple_pass_reset\AccessChecks\ResetPassAccessCheck::access'
      );
    }
  }

}
