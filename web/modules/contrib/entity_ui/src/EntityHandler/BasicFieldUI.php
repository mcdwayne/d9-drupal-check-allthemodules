<?php

namespace Drupal\entity_ui\EntityHandler;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\PreloadableRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an admin UI for target entities that use Field UI.
 *
 * This provides admin list of Entity Tabs for target entity types that do not
 * have bundle entities, but do use Field UI.
 */
class BasicFieldUI extends EntityUIAdminBase {

  /**
   * The entity type's field UI base route.
   */
  protected $fieldUiBaseRoute;

  /**
   * Constructs a new BasicFieldUI.
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
    parent::__construct($entity_type, $entity_type_manager, $route_provider);

    $this->fieldUiBaseRouteName = $this->entityType->get('field_ui_base_route');
  }

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoutePath(RouteCollection $route_collection) {
    $field_ui_base_route = $route_collection->get($this->fieldUiBaseRouteName);

    if (empty($field_ui_base_route)) {
      throw new \Exception("Field UI base route {$this->fieldUiBaseRouteName} does not exist.");
    }

    return $field_ui_base_route->getPath() . '/entity_ui';
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalTasks($base_plugin_definition) {
    $tasks = [];

    // Tab for the Entity Tabs admin collection route.
    $task = $base_plugin_definition;
    $task['title'] = 'Entity tabs';
    $task['route_name'] = "entity_ui.entity_tab.{$this->entityTypeId}.collection";
    $task['base_route'] = $this->fieldUiBaseRouteName;
    $task['weight'] = 20;

    $tasks[$task['route_name']] = $task;

    // We expect that Field UI will also be adding local tasks here, so no need
    // to check that the base route has its own task.

    return $tasks;
  }

}
