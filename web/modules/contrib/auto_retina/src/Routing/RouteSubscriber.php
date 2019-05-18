<?php

/**
 * @file
 * Contains \Drupal\auto_retina\Routing\RouteSubscriber.
 */

namespace Drupal\auto_retina\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('image.style_private')) {
      $route->setDefault('_controller', '\Drupal\auto_retina\Controller\RetinaImageStyleDownloadController::deliver');
    }
    if ($route = $collection->get('image.style_public')) {
      $route->setDefault('_controller', '\Drupal\auto_retina\Controller\RetinaImageStyleDownloadController::deliver');
    }
  }

}
