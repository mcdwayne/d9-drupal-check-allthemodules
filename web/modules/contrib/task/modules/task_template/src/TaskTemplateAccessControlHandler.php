<?php

namespace Drupal\task_template;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Task Template entity.
 *
 * @see \Drupal\task_template\Entity\TaskTemplate.
 */
class TaskTemplateAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\task_template\Entity\TaskTemplateInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished task template entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published task template entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit task template entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete task template entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add task template entities');
  }

}
