<?php

namespace Drupal\custom_panels_blocks\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('panels.select_block')) {
      $route->setDefaults([
        '_controller' => '\Drupal\custom_panels_blocks\Controller\CustomPanelsBlocksController::selectBlock',
      ]);
    }
  }

}
