<?php

namespace Drupal\stacks;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Widget Instance entity entity.
 *
 * @see \Drupal\stacks\Entity\WidgetInstanceEntity.
 */
class WidgetInstanceEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\stacks\WidgetInstanceEntityInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view published widget instance entity entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit widget instance entity entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete widget instance entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add widget instance entity entities');
  }

}
