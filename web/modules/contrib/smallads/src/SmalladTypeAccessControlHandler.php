<?php

namespace Drupal\smallads;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the smallad type entity type.
 *
 * @see \Drupal\smallads\Entity\SmalladType
 */
class SmalladTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'post smallad');
        break;

      case 'delete':
        return parent::checkAccess($entity, 'delete', $account)->addCacheableDependency($entity);
        break;

      default:
        return parent::checkAccess($entity, $operation, $account);
        break;
    }
  }

}
