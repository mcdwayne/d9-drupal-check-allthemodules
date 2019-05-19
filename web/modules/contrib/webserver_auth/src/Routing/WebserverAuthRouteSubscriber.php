<?php

namespace Drupal\webserver_auth\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;


/**
 * Alters user-related routes and prevents users from
 * accessing to user/password/register/login/logout pages or
 * changes behaviours of those routes.
 */
class WebserverAuthRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Setting our own controller for user pages.
    if ($route = $collection->get('user.login')) {
      $route->setDefault('_controller', '\Drupal\webserver_auth\Controller\WebserverAuthUserControllers::userLogin');
    }

    if ($route = $collection->get('user.logout')) {
      $route->setDefault('_controller', '\Drupal\webserver_auth\Controller\WebserverAuthUserControllers::userLogout');
    }

    if ($route = $collection->get('user.register')) {
      $route->setDefault('_controller', '\Drupal\webserver_auth\Controller\WebserverAuthUserControllers::userRegister');
    }
  }
}