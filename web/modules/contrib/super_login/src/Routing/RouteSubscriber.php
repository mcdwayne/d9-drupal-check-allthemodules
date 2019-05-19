<?php

namespace Drupal\super_login\Routing;

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

   // Remove the page title of the user login page.
    if ($route = $collection->get('user.login')) {
      $config = \Drupal::config('super_login.settings');
      /*
       $route->setDefaults(array(
        '_title' => '',
        '_form' => '\Drupal\user\Form\UserLoginForm',
      ));
       *
       */
    }

    // Remove the page title of the password reset page.
    if ($route = $collection->get('user.pass')) {
       $config = \Drupal::config('super_login.settings');
      /*
       $route->setDefaults(array(
        '_title' => '',
        '_form' => '\Drupal\user\Form\UserPasswordForm',
      ));
       *
       */
    }
  }
}
