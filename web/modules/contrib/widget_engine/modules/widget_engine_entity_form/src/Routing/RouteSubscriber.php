<?php

namespace Drupal\widget_engine_entity_form\Routing;

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
    $routes = $collection->all();
    foreach ($routes as $route_name => $route) {
      switch ($route_name) {
        case 'entity_browser.edit_form':
          $route->setDefaults(['_controller' => '\Drupal\widget_engine_entity_form\Controller\WidgetEntityBrowserController::entityBrowserEdit']);
          break;

        default;
      }
    }
  }

}
