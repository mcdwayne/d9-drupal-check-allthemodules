<?php

namespace Drupal\entity_ui\EntityHandler;

use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines the interface for Entity UI admin handlers.
 *
 * An Entity UI admin handler creates the admin UI for the collection of
 * Entity Tabs of the target entity type. This allows each different target
 * entity type to have its own admin UI for its Entity Tabs, which is added to
 * its core admin UI in a way that's suitable for the menu and tab structure.
 * For example, node Entity Tabs are added as a tab alongside the list of node
 * types, whereas user Entity Tabs are added alongside the user field admin tabs
 * as there are no user types.
 *
 * @see entity_ui_entity_type_build()
 */
interface EntityUIAdminInterface {

  /**
   * Gets the routes for the entity type.
   *
   * This is called from a dynamic routing event subscriber, so routes defined
   * so far are available from the event. This includes static routes, and those
   * from other dynamic subscribers with a priority higher than ours.
   *
   * @see \Drupal\entity_ui\Routing\AdminRouteProviderSubscriber
   *
   * @param \Symfony\Component\Routing\RouteCollection $route_collection
   *   The collection of routes built so far.
   *
   * @return array
   *  An array of route objects.
   */
  public function getRoutes(RouteCollection $route_collection);

  /**
   * Gets local task plugin derivatives.
   *
   * @param array $base_plugin_definition
   *   The definition array of the base plugin.
   *
   * @return
   *  An array of plugin derivatives for the local task for the entity type.
   */
  public function getLocalTasks($base_plugin_definition);

  /**
   * Alter local tasks.
   *
   * @param $local_tasks
   *  The array of local tasks passed to hook_local_tasks_alter().
   *
   * @see entity_ui_local_tasks_alter()
   */
  public function localTasksAlter(&$local_tasks);

  /**
   * Gets local action plugin derivatives.
   *
   * @param array $base_plugin_definition
   *   The definition array of the base plugin.
   *
   * @return
   *  An array of plugin derivatives for the local actions for the entity type.
   */
  public function getLocalActions($base_plugin_definition);

}
