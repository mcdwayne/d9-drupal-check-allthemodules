<?php

namespace Drupal\access_by_entity\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Devel routes.
 *
 * @see \Drupal\devel\Controller\EntityDebugController
 * @see \Drupal\devel\Plugin\Derivative\DevelLocalTask
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
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
    foreach (
      $this->entityTypeManager->getDefinitions() as $entity_type_id =>
      $entity_type
    ) {
      if ($route = $this->getEntityLoadRoute($entity_type)) {
        $collection->add("entity.$entity_type_id.access_entity", $route);
      }
    }
  }

  /**
   * Gets the entity load route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getEntityLoadRoute(EntityTypeInterface $entity_type) {
    if ($access_load = $entity_type->getLinkTemplate('access-entity')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($access_load);
      $route
        ->addDefaults([
          '_form' => '\Drupal\access_by_entity\Form\AccessByEntityForm',
          '_title' => 'Restrict access',
        ])
        ->addRequirements([
          '_permission' => 'administer access by entity',
        ])
        ->setOption('_admin_route', TRUE)
        ->setOption('_access_entity_type_id', $entity_type_id)
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      return $route;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', 100];
    return $events;
  }

}
