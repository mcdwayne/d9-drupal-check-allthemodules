<?php
namespace Drupal\login_register_path\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change controller who handle user.
    $config = \Drupal::config('login_register_path.settings');
    if ($config->get('enable')) {
      if (!empty($config->get('login_path'))) {
        if ($route = $collection->get('user.login')) {
          $route->setPath($config->get('login_path'));
        }
      }  
      if (!empty($config->get('register_path'))) {
        if ($route = $collection->get('user.register')) {
          $route->setPath($config->get('register_path'));
        }
      }
      if ($config->get('password_path')) {
        if ($route = $collection->get('user.pass')) {
          $route->setPath($config->get('password_path'));
        }
      }  
      if (!empty($config->get('logout_path'))) {
        if ($route = $collection->get('user.logout')) {
          $route->setPath($config->get('logout_path'));
        }
      }
      if (!empty($config->get('profile_path'))) {
        if ($route = $collection->get('user.page')) {
          $route->setPath($config->get('profile_path'));
        }
      }

    }

  }

}
