<?php

namespace Drupal\fz152\Routing;

use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes.
 */
class Fz152Routes {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = [];
    $plugin_service = \Drupal::service('plugin.manager.fz152');
    foreach ($plugin_service->getDefinitions() as $plugin_id => $plugin) {
      $instance = $plugin_service->createInstance($plugin_id);
      $route_info = $instance->getSettingsPage();
      if (!empty($route_info)) {
        $routes['fz152.settings.' . $plugin_id] = new Route(
          '/admin/config/system/fz152/' . $route_info['path'],
          [
            '_form' => $route_info['form'],
            '_title' => $route_info['title'],
          ],
          [
            '_permission' => 'administer fz152',
          ]
        );
      }
    }

    return $routes;
  }

}