<?php

/**
 * @file
 * Contains \Drupal\tarpit\Routing\RouteController.
 */
namespace Drupal\tarpit\Routing;

use Symfony\Component\Routing\Route;

class RouteController {
  public function routes() {
    $paths = (array) \Drupal::config('tarpit.config')->get('paths');
    $depth = \Drupal::config('tarpit.config')->get('depth');
    $page_title = \Drupal::config('tarpit.config')->get('page_title');

    $routes = array();
    foreach($paths as $path_index => $path) {
      $subpath = array($path);
      $defaults = array();
      for ($i=0; $i<$depth; $i++) {
        $subpath[] = '{arg' . $i . '}';
        $defaults['arg' . $i] = '';
      }
      $defaults += array(
        '_controller' => '\Drupal\tarpit\Controller\PageController::main',
        '_title' => $page_title,
      );

      $subpath = implode('/', $subpath);
      $routes['tarpit.page' . $path_index] = new Route(
        '/' . $subpath,
        $defaults,
        array(
          '_permission' => 'access content',
        )
      );
    }

    return $routes;
  }

}
