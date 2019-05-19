<?php

namespace Drupal\synergy\Routing;

use \Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for entity translation routes.
 */
class SynergyRouteSubscriber extends RouteSubscriberBase {

  /**
   * The content translation manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SynergyRouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      // Try to get the route from the current collection.
      $link_template = $entity_type->getLinkTemplate('canonical');
      if (strpos($link_template, '/') !== FALSE) {
        $base_path = '/' . $link_template;
      }
      else {
        if (!$entity_route = $collection->get("entity.$entity_type_id.canonical")) {
          continue;
        }
        $base_path = $entity_route->getPath();
      }

      // Inherit admin route status from edit route, if exists.
      $is_admin = FALSE;
      $route_name = "entity.$entity_type_id.edit_form";
      if ($edit_route = $collection->get($route_name)) {
        $is_admin = (bool) $edit_route->getOption('_admin_route');
      }

      $path = $base_path . '/synergy';

      $route = new Route(
        $path,
        array(
          '_controller' => '\Drupal\synergy\Controller\SynergyController::overview',
          'entity_type_id' => $entity_type_id,
        ),
        array(
          '_entity_access' => $entity_type_id . '.view'
        ),
        array(
          'parameters' => array(
            $entity_type_id => array(
              'type' => 'entity:' . $entity_type_id,
            ),
          ),
          '_admin_route' => $is_admin,
        )
      );
      $route_name = "entity.$entity_type_id.synergy";
      $collection->add($route_name, $route);
    }
  }

}
