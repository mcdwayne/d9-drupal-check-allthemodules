<?php

namespace Drupal\entity_ui\EntityHandler;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\PreloadableRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides an admin UI for entity tabs on target entities with bundles.
 *
 * This expects the entity type to have a bundle entity type, and this bundle
 * entity type to have a 'collection' link template.
 *
 * The UI for entity tabs is added as a tab alongside the bundle entity
 * collection. So for example, the admin list of entity tabs on nodes is a tab
 * alongside the list of node types.
 */
class BundleEntityCollection extends EntityUIAdminBase implements EntityUIAdminInterface {

  /**
   * The entity type ID of the bundle entity of the target entity.
   */
  protected $bundleEntityTypeID;

  /**
   * The entity type of the bundle entity of the target entity.
   */
  protected $bundleEntityType;

  /**
   * The route name for the collection of the bundle entity.
   */
  protected $bundleCollectionRouteName;

  /**
   * Constructs a new BundleEntityCollection.
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

    $this->bundleEntityTypeID = $entity_type->getBundleEntityType();
    $this->bundleEntityType = $entity_type_manager->getDefinition($this->bundleEntityTypeID);
  }

  /**
   * Looks up the route name of the bundle entity type's collection route.
   *
   * @return string|null
   *  The route name, or NULL if no route exists.
   */
  protected function getBundleCollectionRouteName() {
    // Statically cache this as it's needed in several places.
    if (isset($this->bundleCollectionRouteName)) {
      return $this->bundleCollectionRouteName;
    }

    // For core entities, the route name is consistently of the form
    // 'entity.TYPE.collection', but other modules may use a different route
    // name, especially if they don't use or override AdminHtmlRouteProvider.
    // Therefore, we need to properly search for this.
    $bundle_collection_link_template = $this->bundleEntityType->getLinkTemplate('collection');

    $found_routes = $this->routeProvider->getRoutesByPattern($bundle_collection_link_template);
    $route_iterator = $found_routes->getIterator();

    if (count($route_iterator)) {
      reset($route_iterator);
      $this->bundleCollectionRouteName = key($route_iterator);
      return $this->bundleCollectionRouteName;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoutePath(RouteCollection $route_collection) {
    $bundle_collection_link_template = $this->bundleEntityType->getLinkTemplate('collection');
    return $bundle_collection_link_template . '/entity_ui';
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalTasks($base_plugin_definition) {
    $tasks = [];

    // Bail for entity types that neglect to actually define their collection
    // route, or define it incorrectly.
    $bundle_collection_route_name = $this->getBundleCollectionRouteName();
    if (empty($bundle_collection_route_name)) {
      return $tasks;
    }

    // Tab for the Entity Tabs admin collection route.
    $task = $base_plugin_definition;
    $task['title'] = 'Entity tabs';
    $task['route_name'] = "entity_ui.entity_tab.{$this->entityTypeId}.collection";
    $task['base_route'] = $bundle_collection_route_name;
    $task['weight'] = 20;

    $tasks[$task['route_name']] = $task;

    // Add a default tab for the type collection.
    // If there is one already, localTasksAlter() will remove it.
    $task = $base_plugin_definition;
    $task['title'] = $this->bundleEntityType->getCollectionLabel();
    $task['route_name'] = $bundle_collection_route_name;
    $task['base_route'] = $bundle_collection_route_name;
    $task['weight'] = 0;

    $tasks['entity_ui.' . $bundle_collection_route_name] = $task;

    return $tasks;
  }

  /**
   * {@inheritdoc}
   */
  public function localTasksAlter(&$local_tasks) {
    // Bail early if there's no route.
    $bundle_collection_route_name = $this->getBundleCollectionRouteName();
    if (empty($bundle_collection_route_name)) {
      return;
    }

    // Determine whether the bundle entity collection already has a task.
    // We expect this to be the default task, that is, the base route and the
    // route are the same.
    foreach ($local_tasks as $plugin_id => $local_task) {
      if ($local_task['base_route'] == $bundle_collection_route_name &&
          $local_task['route_name'] == $bundle_collection_route_name &&
          $local_task['id'] != 'entity_ui.admin_local_tasks') {
        // We've found one, so remove the task that we added, as it's surplus.
        unset($local_tasks['entity_ui.admin_local_tasks:entity_ui.' . $bundle_collection_route_name]);
        // We're done with this entity type. Bail.
        return;
      }
    }
  }

}
