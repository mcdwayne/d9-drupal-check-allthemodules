<?php

/**
 * @file
 * Contains \Drupal\tarpit_captcha\Routing\RouteController.
 */
namespace Drupal\tarpit_captcha\Routing;

use Symfony\Component\Routing\Route;

class RouteController {
  public function routes() {
    $depth = \Drupal::config('tarpit.config')->get('depth');
    $page_title = \Drupal::config('tarpit.config')->get('page_title');

    $subpath = array('/tarpit');
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
    $routes['tarpit.page'] = new Route(
      '/' . $subpath,
      $defaults,
      array(
        '_permission' => 'access content',
      )
    );

    return $routes;
  }

}
