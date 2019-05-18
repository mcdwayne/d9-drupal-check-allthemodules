<?php

namespace Drupal\pricespider\Routing;

use Symfony\Component\Routing\Route;

/**
 * Class PriceSpiderRoutes.
 */
class PriceSpiderRoutes {

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $routes = [];

    // Have a uri for the Where to buy page?
    if ($wtb_uri = \Drupal::config('pricespider.settings')->get('wtb.uri')) {
      $routes['pricespider.wtb'] = new Route(
      // Path to attach route to.
        $wtb_uri,
        // Route defaults.
        [
          '_controller' => '\Drupal\pricespider\Controller\WhereToBuyController::content',
          '_title' => 'Where to Buy',
        ],
        // Route requirements.
        [
          '_permission' => 'access content',
        ]
      );
    }

    return $routes;
  }

}
