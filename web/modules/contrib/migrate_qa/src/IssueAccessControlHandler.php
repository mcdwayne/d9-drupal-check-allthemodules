<?php

namespace Drupal\migrate_qa;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class IssueAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // $operation comes from routing.yml file.
    // Permission check is base don permissions.yml file.
    $admin_permission = $this->entityType->getAdminPermission();
    if (\Drupal::currentUser()->hasPermission($admin_permission)) {
      return AccessResult::allowed();
    }
    // @todo Check access to referenced (migrated) entity.
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view migrate_qa_issue entity');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit migrate_qa_issue entity');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete migrate_qa_issue entity');
    }
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // This is if routing.yml file is checking a create action.
    // Permission check is based on permissions.yml file.
    $admin_permission = $this->entityType->getAdminPermission();
    if (\Drupal::currentUser()->hasPermission($admin_permission)) {
      return AccessResult::allowed();
    }
    return AccessResult::allowedIfHasPermission($account, 'add migrate_qa_issue entity');
  }
}
