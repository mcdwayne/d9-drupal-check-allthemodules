<?php

/**
 * @file
 * Contains \Drupal\block_page\Routing\BlockPageRoutes.
 */

namespace Drupal\block_page\Routing;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteCompiler;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes for block pages.
 */
class BlockPageRoutes extends RouteSubscriberBase {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Constructs a new BlockPageRoutes.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityStorage = $entity_manager->getStorage('block_page');
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityStorage->loadMultiple() as $entity_id => $entity) {
      /** @var $entity \Drupal\block_page\BlockPageInterface */

      // Prepare a route name to use if this is a custom page.
      $route_name = "block_page.page_view_$entity_id";

      // Prepare the values that need to be altered for an existing page.
      $path = '/' . $entity->getPath();
      $parameters = array(
        'block_page' => array(
          'type' => 'entity:block_page',
        ),
      );

      // Loop through all existing routes to see if this is overriding a route.
      foreach ($collection->all() as $name => $collection_route) {
        // Find all paths which match the path of the current display.
        $route_path = RouteCompiler::getPathWithoutDefaults($collection_route);
        $route_path = RouteCompiler::getPatternOutline($route_path);

        if ($path == $route_path) {
          // Adjust the path to translate %placeholders to {slugs}.
          $path = $collection_route->getPath();

          // Merge in any route parameter definitions.
          $parameters += $collection_route->getOption('parameters');

          // Update the route name this will be added to.
          $route_name = $name;
          // Remove the existing route.
          $collection->remove($route_name);
          break;
        }
      }

      // Construct an add a new route.
      $route = new Route(
        $path,
        array(
          '_entity_view' => 'block_page',
          'block_page' => $entity_id,
          '_title' => $entity->label(),
        ),
        array(
          '_entity_access' => 'block_page.view',
        ),
        array(
          'parameters' => $parameters,
        )
      );
      $collection->add($route_name, $route);
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    // Run after EntityRouteAlterSubscriber.
    $events[RoutingEvents::ALTER][] = array('onAlterRoutes', -160);
    return $events;
  }

}
