<?php

namespace Drupal\search_365\Routing;

use Symfony\Component\Routing\Route;

/**
 * Class DynamicRoute.
 */
class SearchViewRoute {

  const ROUTE_NAME = 'search_365.search_view';

  /**
   * Get route dynamically from system settings.
   */
  public function getRoute() {
    $searchDisplaySettings = \Drupal::configFactory()->get('search_365.settings')->get('display_settings');

    $drupalPath = $searchDisplaySettings['drupal_path'];
    $title = $searchDisplaySettings['search_title'];

    if (NULL === $drupalPath) {
      return NULL;
    }

    // . '/{$searchQuery}'.
    $routes[self::ROUTE_NAME] = new Route(
      '/' . $drupalPath . '/{search_query}',
      [
        '_title' => $title,
        '_controller' => '\Drupal\search_365\Controller\SearchViewController::get',
        'search_query' => '',
      ],
      [
        '_permission' => 'access search 365 content',
      ]
    );

    return $routes;

  }

}
