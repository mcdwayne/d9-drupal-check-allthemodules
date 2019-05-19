<?php

namespace Drupal\stacks;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Widget Entity entity.
 *
 * @see \Drupal\stacks\Entity\WidgetEntity.
 */
class WidgetEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\stacks\WidgetEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished stacks entity entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published stacks entity entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit stacks entity entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete stacks entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add stacks entity entities');
  }

}
