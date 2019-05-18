<?php

namespace Drupal\route_iframes\Routing;

use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes for Route Iframes module.
 */
class IframeRoutes {

  /**
   * Provide the dynamic routes for the route_iframes module.
   *
   * @return array
   *   An array of Route objects.
   */
  public function routes() {
    $routes = [];

    $config = \Drupal::config('route_iframes.routeiframesconfiguration');
    $name = $config->get('route_iframe_main_tab_name');
    $path = $config->get('route_iframe_main_tab_path');
    $tabs = $config->get('route_iframe_tabs');

    if (!empty($path) && !empty($name)) {
      if (!empty($tabs)) {
        foreach ($tabs as $tab) {
          $routes['route_iframes.' . $path . '.' . $tab['path']] = new Route(
            '/node/{node}/' . $path . '/' . $tab['path'],
            [
              '_controller' => '\Drupal\route_iframes\Controller\RouteIframeController::build',
              '_title' => $name,
              'tab' => $tab['path'],
            ],
            [
              '_permission' => 'view route iframe pages',
              '_custom_access' => '\Drupal\route_iframes\Controller\RouteIframeController::validConfig',
            ],
            [
              '_admin_route' => TRUE,
              'parameters' => [
                'node' => [
                  'type' => 'entity:node',
                ],
              ],
            ]
          );
        }
      }
      else {
        $routes['route_iframes.' . $path] = new Route(
          '/node/{node}/' . $path,
          [
            '_controller' => '\Drupal\route_iframes\Controller\RouteIframeController::build',
            '_title' => $name,
          ],
          [
            '_permission' => 'view route iframe pages',
            '_custom_access' => '\Drupal\route_iframes\Controller\RouteIframeController::validConfig',
          ],
          [
            '_admin_route' => TRUE,
            'parameters' => [
              'node' => [
                'type' => 'entity:node',
              ],
            ],
          ]
        );
      }
    }
    return $routes;
  }

}
