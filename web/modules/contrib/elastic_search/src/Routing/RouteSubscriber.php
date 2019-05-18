<?php

namespace Drupal\elastic_search\Routing;

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
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($route = $this->getEntityLoadRoute($entity_type)) {
        $collection->add("entity.$entity_type_id.elastic_edit", $route);
        $collection->add("entity.$entity_type_id.elastic_mapping_add", $route);
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
    if ($elasticAddEdit = $entity_type->getLinkTemplate('elastic-mapping-add')) {
      $entity_type_id = $entity_type->id();
      $route = new Route($elasticAddEdit);
      //Controller deals with the logic for add/edit redirects
      $route
        ->addDefaults([
                        '_controller' => '\Drupal\elastic_search\Controller\MappingController::entityLoad',
                        '_title'      => 'Elastic Load',
                      ])
        ->addRequirements([
                            '_permission' => 'administer elasticsearch',
                          ])
        ->setOption('_admin_route', TRUE)
        ->setOption('_elastic_entity_type_id', $entity_type_id)
        ->setOption('parameters',
                    [
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
