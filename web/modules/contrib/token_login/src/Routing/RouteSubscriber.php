<?php

namespace Drupal\token_login\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\token_login\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Use the password reset form for the login page.
    if ($route = $collection->get('user.login')) {
      $route->setDefaults(['_form' => '\Drupal\token_login\Form\TokenLoginForm']);
    }

    // Always deny access to '/user/logout'.
    // Note that the second parameter of setRequirement() is a string.
    if ($route = $collection->get('user.pass')) {
      $route->setRequirement('_access', 'FALSE');
    }

    // Change the title of the password reset page.
    if ($route = $collection->get('user.reset')) {
      $route->setDefaults([
        '_title' => 'Use log in link',
        '_controller' => '\Drupal\token_login\Controller\TokenLoginUserController::resetPass',
      ]);
    }
  }

}
