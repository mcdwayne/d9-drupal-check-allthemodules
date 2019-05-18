<?php

namespace Drupal\entity_ui\EntityHandler;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\PreloadableRouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Base class for Entity UI admin handlers.
 *
 * @see entity_ui_entity_type_build()
 */
abstract class EntityUIAdminBase implements EntityHandlerInterface {

  /**
   * The entity type this handler is for.
   */
  protected $entityType;

  /**
   * The ID of the entity type this handler is for.
   */
  protected $entityTypeId;

  /**
   * The route provider service.
   *
   * @var \Drupal\Core\Routing\PreloadableRouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Constructs a new EntityUIAdminBase.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\PreloadableRouteProviderInterface $route_provider
   *   The route provider service.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityTypeManagerInterface $entity_type_manager,
    PreloadableRouteProviderInterface $route_provider
    ) {
    $this->entityTypeId = $entity_type->id();
    $this->entityType = $entity_type;
    $this->entityTypeManager = $entity_type_manager;
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes(RouteCollection $route_collection) {
    $routes = [];

    if ($route = $this->getCollectionRoute($route_collection)) {
      $routes["entity_ui.entity_tab.{$this->entityTypeId}.collection"] = $route;
    }

    return $routes;
  }

  /**
   * Builds the route for the partial collection of entity UI tab entities.
   *
   * @param \Symfony\Component\Routing\RouteCollection $route_collection
   *   The collection of routes built so far.
   *
   * @return \Symfony\Component\Routing\Route
   *  The route for the collection.
   */
  protected function getCollectionRoute(RouteCollection $route_collection) {
    $collection_route_path = $this->getCollectionRoutePath($route_collection);

    $route = new Route($collection_route_path);
    $route
      ->addDefaults([
        '_entity_list' => 'entity_tab',
        '_title' => '@label tabs',
        '_title_arguments' => ['@label' => $this->entityType->getLabel()],
      ])
      ->addOptions([
        '_target_entity_type_id' => $this->entityTypeId,
      ])
      // Combine the blanket and specific type permissions with an OR.
      ->setRequirement('_permission', 'administer all entity tabs+administer ' . $this->entityTypeId . ' entity tabs');

    return $route;
  }

  /**
   * Returns the path for the collection route.
   *
   * @param \Symfony\Component\Routing\RouteCollection $route_collection
   *   The collection of routes built so far.
   *
   * @return string
   *  The path for the collection route.
   */
  protected abstract function getCollectionRoutePath(RouteCollection $route_collection);

  /**
   * {@inheritdoc}
   */
  public function getLocalTasks($base_plugin_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function localTasksAlter(&$local_tasks) {
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalActions($base_plugin_definition) {
    $actions = [];

    $action = $base_plugin_definition;
    $action = [
      'route_name' => "entity.entity_tab.add_page",
      'route_parameters' => [
        'target_entity_type_id' => $this->entityTypeId,
      ],
      'title' => t('Add entity tab'),
      'appears_on' => array("entity_ui.entity_tab.{$this->entityTypeId}.collection"),
    ];

    $actions["entity_ui.entity_tab.{$this->entityTypeId}.collection.add"] = $action;

    return $actions;
  }

}
