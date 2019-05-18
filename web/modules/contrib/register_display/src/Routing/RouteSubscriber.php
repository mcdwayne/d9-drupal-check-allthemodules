<?php

namespace Drupal\register_display\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\register_display\RegisterDisplayServices;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {
  protected $services;

  /**
   * {@inheritdoc}
   */
  public function __construct(RegisterDisplayServices $services) {
    $this->services = $services;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($this->services->isRedirectDefaultRegisterPage() && $route = $collection->get('user.register')) {
      $route->setDefaults([
        '_controller' => '\Drupal\register_display\Controller\UserPagesController::redirectControl',
      ]);
    }

  }

}
