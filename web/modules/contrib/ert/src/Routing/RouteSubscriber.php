<?php

/**
 * @file
 * Contains \Drupal\ert\Routing\RouteSubscriber.
 */

namespace Drupal\ert\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for ert routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($route = $this->getEntityReadTimeRoute($entity_type)) {
        $collection->add("entity.$entity_type_id.read_time", $route);
      }
    }
  }

  /**
   * Gets the Entity Read time route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getEntityReadTimeRoute(EntityTypeInterface $entity_type) {
    if ($route_load = $entity_type->getLinkTemplate('read-time')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($route_load);
      $route
        ->addDefaults([
          '_form' => '\Drupal\ert\Form\ReadTimeForm',
          '_title' => 'Read time',
        ])
        ->addRequirements([
          '_permission' => 'administer ' . $entity_type_id . ' read time',
        ])
        ->setOption('_admin_route', TRUE)
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:'.$entity_type_id],
        ]);
      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', -100);
    return $events;
  }

}
