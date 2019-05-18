<?php

namespace Drupal\group_workflow\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupContentType;

/**
 * Checks access for displaying workflow tab on group nodes.
 */
class GroupWorkflowAccessCheck implements AccessInterface {

  /**
   * Custom access check for workflow tab on group nodes.
   *
   * NOTE: Per the drupal.org documentation at
   * https://www.drupal.org/docs/8/api/routing-system/access-checking-on-routes
   * this must return "allowed" for situations where you would expect "neutral"
   * to be the correct response, as routing uses "andIf" logic, while entity
   * uses "orIf".
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Returns Allowed, neutral or not allowed.
   */
  public function access(AccountInterface $account) {
    if ($account->hasPermission('bypass group workflow check')) {
      return AccessResult::allowed();
    }

    // Load relevant entity.
    $entity = workflow_url_get_entity();

    // Determine if this content type is even managed by groups at all.
    $plugin_id = 'group_node:' . $entity->bundle();
    $group_content_types = GroupContentType::loadByContentPluginId($plugin_id);

    if (empty($group_content_types)) {
      return AccessResult::allowed();
    }

    // Load all the group content for this node.
    $group_contents = \Drupal::entityTypeManager()
      ->getStorage('group_content')
      ->loadByProperties([
        'type' => array_keys($group_content_types),
        'entity_id' => $entity->id(),
      ]);

    // If the node does not belong to any group, we have nothing to say.
    if (empty($group_contents)) {
      return AccessResult::allowed();
    }

    $groups = [];
    foreach ($group_contents as $group_content) {
      /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
      $group = $group_content->getGroup();
      $groups[$group->id()] = $group;
    }

    // Check if user has permission for all groups.
    $permission_pass = NULL;

    foreach ($groups as $group) {
      if ($group->hasPermission('access group_node workflow', $account)) {
        $permission_pass = TRUE;
      }
      else {
        $permission_pass = FALSE;
      }
    }

    return $permission_pass ?
      AccessResult::allowed() : AccessResult::forbidden();
  }

}
