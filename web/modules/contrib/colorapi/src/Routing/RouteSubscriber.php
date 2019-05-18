<?php

namespace Drupal\colorapi\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $colorapi_color_entity_routes = [
      'entity.colorapi_color.collection',
      'entity.colorapi_color.add_form',
      'entity.colorapi_color.edit_form',
      'entity.colorapi_color.delete_form',
    ];

    foreach ($colorapi_color_entity_routes as $route_name) {
      if ($route = $collection->get($route_name)) {
        $color_entity_enabled = \Drupal::config('colorapi.settings')->get('enable_color_entity');
        if (!$color_entity_enabled) {
          $collection->remove($route_name);
        }
      }
    }
  }

}
