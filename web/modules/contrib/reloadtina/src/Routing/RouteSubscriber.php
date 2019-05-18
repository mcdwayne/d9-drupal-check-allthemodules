<?php

namespace Drupal\reloadtina\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('image.style_private')) {
      $this->transformRoute($route);
    }
    if ($route = $collection->get('image.style_public')) {
      $this->transformRoute($route);
    }
  }

  /**
   * Hijack image style deliver route, and apply multiplication.
   */
  protected function transformRoute(Route $route) {
    $route->setDefault('_controller', '\Drupal\reloadtina\Controller\ImageStyleDownloadController::deliver');
    $options = $route->getOptions();
    $options['parameters']['image_style'] = [
      'type' => 'reloadtina.image_style',
    ];
    $route->setOptions($options);
  }
}
