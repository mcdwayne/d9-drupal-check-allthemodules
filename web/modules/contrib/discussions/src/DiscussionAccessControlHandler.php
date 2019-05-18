<?php

namespace Drupal\discussions;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupContentType;

/**
 * Defines the access control handler for the discussion entity type.
 *
 * @see \Drupal\discussions\Entity\Discussion
 */
class DiscussionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Skip access check on new entities.
    // @see \Drupal\discussions\Access\GroupDiscussionAddAccessCheck.
    if ($entity->isNew()) {
      return AccessResult::neutral();
    }

    $type = $entity->bundle();

    // Skip access check if no group content types for this discussion type.
    $group_content_types = GroupContentType::loadByContentPluginId("group_discussion:$type");
    if (empty($group_content_types)) {
      return AccessResult::neutral();
    }

    // Load all the group content for this discussion.
    $group_contents = \Drupal::entityTypeManager()
      ->getStorage('group_content')
      ->loadByProperties([
        'type' => array_keys($group_content_types),
        'entity_id' => $entity->id(),
      ]);

    // Skip access check if the discussion does not belong to a group.
    if (empty($group_contents)) {
      return AccessResult::neutral();
    }

    /** @var \Drupal\group\Entity\GroupInterface[] $groups */
    $groups = [];
    foreach ($group_contents as $group_content) {
      /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
      $group = $group_content->getGroup();
      $groups[$group->id()] = $group;
    }

    /** @var \Drupal\discussions\Entity\Discussion $entity */
    switch ($operation) {
      case 'view':
        foreach ($groups as $group) {
          if ($group->hasPermission("view $type discussion", $account)) {
            return AccessResult::allowed();
          }
        }
        break;

      case 'update':
        foreach ($groups as $group) {
          if ($group->hasPermission("edit any $type discussion", $account)) {
            return AccessResult::allowed();
          }
          elseif ($account->id() == $entity->get('uid') && $group->hasPermission("edit own $type discussion", $account)) {
            return AccessResult::allowed();
          }
        }
        break;

      case 'delete':
        foreach ($groups as $group) {
          if ($group->hasPermission("delete any $type discussion", $account)) {
            return AccessResult::allowed();
          }
          elseif ($account->id() == $entity->get('uid') && $group->hasPermission("delete own $type discussion", $account)) {
            return AccessResult::allowed();
          }
        }
        break;
    }

    return AccessResult::forbidden();
  }

}
