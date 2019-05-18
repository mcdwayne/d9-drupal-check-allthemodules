<?php

namespace Drupal\route_path_rewrite\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\route_path_rewrite\Config\ConfigManager;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Array of routes that should be rewritten.
   *
   * @var array[\Drupal\route_path_rewrite\Config\RouteRewriteConfig]
   */
  private $routesToRewrite;

  /**
   * RouteSubscriber constructor.
   *
   * @param \Drupal\route_path_rewrite\Config\ConfigManager $configManager
   *   The config factory service.
   */
  public function __construct(ConfigManager $configManager) {
    $this->routesToRewrite = $configManager->getRoutesToRewrite();
  }

  /**
   * Changes the paths of routes that should be rewritten.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   The route collection.
   */
  public function alterRoutes(RouteCollection $collection) {
    foreach ($this->routesToRewrite as $routeToRewrite) {
      $route = $collection->get($routeToRewrite->getName());

      if ($route instanceof Route) {
        $collection->get($routeToRewrite->getName())->setPath($routeToRewrite->getNewPath());
      }
    }
  }

}
