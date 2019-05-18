<?php

namespace Drupal\locker\Routing;

use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes.
 */
class LockerRoutes {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = [];
    $config = \Drupal::config('locker.settings');
    $uri = $config->get('locker_custom_url');
    if (!$uri) {
      $uri = 'unlock.html';
    }
    $routes['locker.register'] = new Route(
    '/' . $uri,
      [
        '_controller' => '\Drupal\locker\Controller\LockCont::content',
        '_title' => 'Locker',
      ],
      [
        '_permission'  => 'access content',
      ]
    );

    return $routes;
  }

}
