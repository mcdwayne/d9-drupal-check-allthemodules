<?php

namespace Drupal\entity_ui\EntityHandler;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\PreloadableRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides an admin UI for entity tabs on target entities with plain bundles.
 *
 * This is for target entity types that have multiple bundles, but where those
 * bundles are not config entities. This includes, for example, entity types
 * whose bundles are derived from plugins using the Entity API contrib module's
 * functionality, or simnply hardcoded in hook_entity_bundle_info().
 *
 * This expects the entity type to have a field UI base route defined, and for
 * that route's path to end in '/{bundle}', and for there to be a route for the
 * base of the entity type's admin UI whose path is the same as the field UI
 * path but with the '/{bundle}' component removed. So for example, an entity
 * type might define two routes with these paths:
 *  - /admin/structure/my-type
 *  - /admin/structure/my-type/{bundle}
 *
 * The UI for entity tabs is added as a tab alongside the admin base route.
 */
class PlainBundlesEntityUIAdmin extends EntityUIAdminBase implements EntityUIAdminInterface {

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

    $field_ui_base_route_path = $field_ui_base_route->getPath();
    if (substr($field_ui_base_route_path, -(strlen('{bundle}'))) != '{bundle}') {
      throw new \Exception("Field UI base route {$this->fieldUiBaseRouteName} must end in '/{bundle}'.");
    }

    // Remove the final '/{bundle}' path component and replace with
    // '/entity_ui'.
    $collection_route_path = substr($field_ui_base_route_path, 0, -(strlen('/{bundle}')));
    $collection_route_path .= '/entity_ui';

    return $collection_route_path;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalTasks($base_plugin_definition) {
    $tasks = [];

    $admin_base_route_name = $this->getAdminBaseRouteName();

    // Tab for the Entity Tabs admin collection route.
    $task = $base_plugin_definition;
    $task['title'] = 'Entity tabs';
    $task['route_name'] = "entity_ui.entity_tab.{$this->entityTypeId}.collection";
    $task['base_route'] = $admin_base_route_name;
    $task['weight'] = 20;

    $tasks[$task['route_name']] = $task;

    // Add a default tab for the list of bundles.
    $task = $base_plugin_definition;
    $task['title'] = t('@bundle-label list', [
      '@bundle-label' => $this->entityType->getBundleLabel(),
    ]);
    $task['route_name'] = $admin_base_route_name;

    $task['base_route'] = $admin_base_route_name;
    $task['weight'] = 0;

    $tasks['entity_ui.' . $admin_base_route_name] = $task;

    return $tasks;
  }

  /**
   * Gets the name of the route that's the base of the entity type's admin UI.
   *
   * @return string
   *   The route name
   *
   * @throws \Exception
   *   Throws an exception if the entity type is not configured as expected, or
   *   if the route cannot be found.
   */
  protected function getAdminBaseRouteName() {
    // Probably don't inject this, as we don't want this when we're building
    // routes!
    $route_provider = \Drupal::service('router.route_provider');

    $field_ui_base_route = $route_provider->getRouteByName($this->fieldUiBaseRouteName);
    $field_ui_base_route_path = $field_ui_base_route->getPath();

    if (substr($field_ui_base_route_path, -(strlen('{bundle}'))) != '{bundle}') {
      throw new \Exception("Field UI base route {$this->fieldUiBaseRouteName} must end in '/{bundle}'.");
    }

    // Remove the final '/{bundle}' path component.
    $admin_base_path = substr($field_ui_base_route_path, 0, -(strlen('/{bundle}')));

    // Try to find a route with this path.
    $found_routes = $route_provider->getRoutesByPattern($admin_base_path);
    $route_iterator = $found_routes->getIterator();
    if (!count($route_iterator)) {
      throw new \Exception("Unable to find route for admin base path '{$admin_base_path}'.");
    }

    foreach ($route_iterator as $admin_base_route_name => $admin_base_route) {
      // Should be only one route.
      break;
    }

    return $admin_base_route_name;
  }

}
