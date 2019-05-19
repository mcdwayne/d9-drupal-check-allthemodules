<?php

namespace Drupal\workflow_field_groups\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines an access check for workflow field groups form modes.
 *
 * @see \Drupal\Core\Entity\Entity\EntityFormMode
 */
class WorkflowFieldGroupsAccessCheck implements AccessInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to the workflow field groups form mode.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param string $form_mode_name
   *   (optional) The form mode. Defaults to 'default'.
   * @param string $bundle
   *   (optional) The bundle. Different entity types can have different names
   *   for their bundle key, so if not specified on the route via a {bundle}
   *   parameter, the access checker determines the appropriate key name, and
   *   gets the value from the corresponding request attribute. For example,
   *   for nodes, the bundle key is "node_type", so the value would be
   *   available via the {node_type} parameter rather than a {bundle}
   *   parameter.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account, $form_mode_name = 'default', $bundle = NULL) {
    $access = AccessResult::neutral();
    if ($entity_type_id = $route->getDefault('entity_type_id')) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      if (empty($bundle)) {
        $bundle = $route_match->getRawParameter($entity_type->getBundleEntityType());
      }

      $bundle_entity_type = $entity_type->getBundleEntityType();
      $entity_type_config_entity = $this->entityTypeManager->getStorage($bundle_entity_type)->load($bundle);
      $enabled = $entity_type_config_entity->getThirdPartySetting('workflow_field_groups', 'enable', FALSE);
      if (!$enabled) {
        $access = AccessResult::forbidden();
        $access->addCacheableDependency($entity_type_config_entity);
        return $access;
      }

      $visibility = FALSE;
      if ($form_mode_name == 'default') {
        $visibility = TRUE;
      }
      elseif ($entity_display = $this->entityTypeManager->getStorage('entity_form_display')->load($entity_type_id . '.' . $bundle . '.' . $form_mode_name)) {
        $visibility = $entity_display->status();
      }

      if ($form_mode_name != 'default' && $entity_display) {
        $access->addCacheableDependency($entity_display);
      }

      if ($visibility) {
        $permission = $route->getRequirement('_workflow_field_groups_access');
        $access = $access->orIf(AccessResult::allowedIfHasPermission($account, $permission));
      }
    }

    return $access;
  }

}
