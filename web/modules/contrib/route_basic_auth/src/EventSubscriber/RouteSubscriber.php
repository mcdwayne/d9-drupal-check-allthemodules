<?php

namespace Drupal\route_basic_auth\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\route_basic_auth\Config\ConfigManager;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Array of routes that should be protected.
   *
   * @var array[\Drupal\route_basic_auth\Config\ProtectedRouteConfig]
   */
  private $protectedRoutes;

  /**
   * RouteSubscriber constructor.
   *
   * @param \Drupal\route_basic_auth\Config\ConfigManager $configManager
   *   The config factory service.
   */
  public function __construct(ConfigManager $configManager) {
    $this->protectedRoutes = $configManager->getProtectedRoutes();
  }

  /**
   * Runs BasicAuthAccessCheck on routes that should be protected.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection.
   */
  public function alterRoutes(RouteCollection $collection) {
    foreach ($this->protectedRoutes as $protectedRoute) {
      $route = $collection->get($protectedRoute->getName());

      if ($route instanceof Route) {
        $collection->get($protectedRoute->getName())->addRequirements(['_route_basic_auth__access_check' => 'TRUE']);
      }
    }
  }

}
