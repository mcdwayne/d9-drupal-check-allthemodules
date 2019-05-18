<?php

namespace Drupal\instapage\Routing;

use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes.
 */
class PagesRoutes {

  /**
   * Dynamically create routes for configured paths.
   *
   * @return array
   */
  public function routes() {
    $routes = [];
    $config = \Drupal::config('instapage.pages');
    $pages = $config->get('instapage_pages');

    if ($pages) {
      foreach ($pages as $instapage_id => $path) {
        $route_key = 'instapage.pages.' . $instapage_id;
        $routes[$route_key] = new Route(
          $path,
          [
            '_controller' => '\Drupal\instapage\Controller\PageDisplayController::content',
            'instapage_id' => $instapage_id,
          ],
          [
            '_permission' => 'access content',
          ]
        );
      }
    }
    return $routes;
  }

}
