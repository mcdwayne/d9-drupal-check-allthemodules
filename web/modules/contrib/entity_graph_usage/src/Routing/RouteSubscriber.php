<?php

namespace Drupal\entity_graph_usage\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\entity_graph_usage\Routing
 * Listens to the dynamic route events.
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entityType => $definition) {
      if ($template = $definition->getLinkTemplate('entity-graph-usage')) {
        $route = new Route($template);
        $route
          ->addDefaults([
            '_controller' => '\Drupal\entity_graph_usage\Controller\DefaultController::list',
            '_title_callback' => '\Drupal\entity_graph_usage\Controller\DefaultController::getTitle',
            'entity_type' => $entityType,
          ])
          ->addRequirements([
            '_permission' => 'access entity usage information',
          ])
          ->addRequirements([
            '_custom_access' => '\Drupal\entity_graph_usage\Controller\DefaultController::checkAccess',
          ])
          ->setOption('_admin_route', TRUE)
          ->setOption('_entity_graph_usage_entity_type', $entityType)
          ->setOption('parameters', [
            $entityType => ['type' => 'entity:' . $entityType],
          ]);

        $collection->add("entity.$entityType.entity_graph_usage", $route);
      }
    }
  }

}
