<?php

namespace Drupal\workflow_task;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Workflow task entity.
 *
 * @see \Drupal\workflow_task\Entity\WorkflowTask.
 */
class WorkflowTaskAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\workflow_task\Entity\WorkflowTaskInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view workflow task entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit workflow task entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete workflow task entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add workflow task entities');
  }

}
