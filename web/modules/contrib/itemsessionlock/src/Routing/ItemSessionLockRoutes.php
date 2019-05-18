<?php

namespace Drupal\itemsessionlock\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Drupal\itemsessionlock\Plugin\ItemSessionLock\ItemSessionLockManager;

/**
 * Defines dynamic routes.
 */
class ItemSessionLockRoutes {

  /**
   * Gathers plugin routes definitions.
   * @return Symfony\Component\Routing\RouteCollection
   */
  public function routes() {
    $route_collection = new RouteCollection();
    $manager = \Drupal::service('plugin.manager.itemsessionlock');
    $locks = $manager->getDefinitions();
    if (!empty($locks)) {
      foreach ($locks as $def) {
        $lock = $manager->createInstance($def['id']);
        $routes = $lock->getRoutes();
        foreach ($routes as $id => $route) {
          $route_collection->add($id, $route);
        }
      }
    }
    return $route_collection;
  }

}
