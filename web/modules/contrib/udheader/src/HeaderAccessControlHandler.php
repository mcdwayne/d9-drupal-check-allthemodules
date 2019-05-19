<?php

namespace Drupal\udheader;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Ubuntu Drupal Header entity.
 *
 * @see \Drupal\udheader\Entity\Header.
 */
class HeaderAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   *
   * Link the activities to the permission.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view udheader entity');
      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit udheader entity');
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete udheader entity');
    }
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   *
   * Separate from the checkAccess because the entity does not yet exist.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add udheader entity');
  }
}
