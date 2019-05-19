<?php

namespace Drupal\virtual_entities;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Virtual entity entity.
 *
 * @see \Drupal\virtual_entities\Entity\VirtualEntity.
 */
class VirtualEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\virtual_entities\VirtualEntityInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view published virtual entity entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit virtual entity entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete virtual entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add virtual entity entities');
  }

}
