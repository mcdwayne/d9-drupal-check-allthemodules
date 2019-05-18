<?php

namespace Drupal\entity_base\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for entity routes.
 */
class EntityBaseRouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RouteSubscriber object.
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

    $entity_base_types = entity_base_types();
    foreach ($entity_base_types as $entityType => $entityDefinition) {

      // Routes for "Current" workflow.
      if (isset($entityDefinition->get('additional')['entity_base']['workflows']['current']) && $entityDefinition->get('additional')['entity_base']['workflows']['current']) {
        // Add "Set as current" route.
        $route = (new Route($entityDefinition->get('links')['current']))
          ->addDefaults([
            '_controller' => $entityDefinition->get('entity_base')['callbacks']['entity.' . $entityType . '.current'],
            '_title' => 'Set as current',
          ])
          ->setOption('no_cache', TRUE)
          ->setRequirement('_entity_access', $entityType . '.update')
        ;
        $collection->add('entity.' . $entityType . '.current', $route);
      }

      // Routes for "Locked" workflow.
      if (isset($entityDefinition->get('additional')['entity_base']['workflows']['locked']) && $entityDefinition->get('additional')['entity_base']['workflows']['locked']) {
        // Add "Lock" route.
        $route = (new Route($entityDefinition->get('links')['lock']))
          ->addDefaults([
            '_controller' => $entityDefinition->get('entity_base')['callbacks']['entity.' . $entityType . '.lock'],
            '_title' => 'Lock',
          ])
          ->setOption('no_cache', TRUE)
          ->setRequirement('_entity_access', $entityType . '.update')
        ;
        $collection->add('entity.' . $entityType . '.lock', $route);

        // Add "Unlock" route.
        $route = (new Route($entityDefinition->get('links')['unlock']))
          ->addDefaults([
            '_controller' => $entityDefinition->get('entity_base')['callbacks']['entity.' . $entityType . '.unlock'],
            '_title' => 'Unlock',
          ])
          ->setOption('no_cache', TRUE)
          ->setRequirement('_entity_access', $entityType . '.update')
        ;
        $collection->add('entity.' . $entityType . '.unlock', $route);
      }

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
