<?php

namespace Drupal\stacks;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Widget Extend entity.
 *
 * @see \Drupal\stacks\Entity\WidgetExtendEntity.
 */
class WidgetExtendEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\stacks\WidgetExtendEntityInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view published Widget Extend entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit Widget Extend entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete Widget Extend entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add Widget Extend entities');
  }

}
