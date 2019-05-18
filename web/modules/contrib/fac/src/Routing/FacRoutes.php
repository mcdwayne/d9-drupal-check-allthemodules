<?php

namespace Drupal\fac\Routing;

use Symfony\Component\Routing\Route;
use Drupal\Core\StreamWrapper\PublicStream;

/**
 * Defines a route subscriber to register a url for service fac json files.
 */
class FacRoutes {

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $routes['fac.json'] = new Route(PublicStream::basePath() . '/fac-json/{fac_config_id}/{langcode}/{hash}/{key}', [
      '_controller' => '\Drupal\fac\Controller\FacController::generateJson',
    ], [
      '_permission' => 'access content',
      'fac_config_id' => '.*?',
      'langcode' => '^[a-z]{2,3}(?:-[A-Z]{2,3}(?:-[a-zA-Z]{4})?)?$',
      'hash' => '.*?',
      'key' => '.*?\.json$',
    ]);

    return $routes;
  }

}
