<?php

namespace Drupal\discussions\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\discussions\DiscussionTypeInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access for adding group Discussions.
 */
class GroupDiscussionAddAccessCheck implements AccessInterface {

  /**
   * Checks a user's permission to create group discussions.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group the Discussion is part of.
   * @param \Drupal\discussions\DiscussionTypeInterface $discussion_type
   *   The type of Discussion to access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, GroupInterface $group, DiscussionTypeInterface $discussion_type) {
    $needs_access = $route->getRequirement('_group_discussion_add_access') === 'TRUE';

    // We can only get the group Discussion Type ID if the plugin is installed.
    $plugin_id = 'group_discussion:' . $discussion_type->id();
    if (!$group->getGroupType()->hasContentPlugin($plugin_id)) {
      return AccessResult::neutral();
    }

    // Determine whether the user can create Discussions of the provided type.
    $access = $group->hasPermission('create ' . $discussion_type->id() . ' discussion', $account);

    return AccessResult::allowedIf($access xor !$needs_access);
  }

}
