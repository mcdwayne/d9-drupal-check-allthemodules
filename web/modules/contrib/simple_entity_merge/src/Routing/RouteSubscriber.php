<?php

namespace Drupal\simple_entity_merge\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for SimpleEntityMerge routes.
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
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($route = $this->getSimpleEntityMergeExecuteRoute($entity_type)) {
        $collection->add("entity.$entity_type_id.simple_entity_merge_execute", $route);
      }
    }
  }

  /**
   * Gets the simple_entity_merge execute route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getSimpleEntityMergeExecuteRoute(EntityTypeInterface $entity_type) {
    if ($simple_entity_merge_execute = $entity_type->getLinkTemplate('simple_entity_merge-execute')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($simple_entity_merge_execute);
      $route
        ->addDefaults([
          '_entity_form' => $entity_type_id . '.simple_entity_merge',
          '_title' => 'Simple Entity Merge',
        ])
        ->addRequirements([
          '_permission' => 'execute simple_entity_merge',
        ])
        ->setOption('_admin_route', TRUE)
        ->setOption('_simple_entity_merge_entity_type_id', $entity_type_id)
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
    $events[RoutingEvents::ALTER] = array('onAlterRoutes', 100);
    return $events;
  }

}
