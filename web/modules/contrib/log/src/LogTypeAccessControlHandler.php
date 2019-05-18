<?php

/**
 * @file
 * Contains \Drupal\log\LogTypeAccessControlHandler.
 */

namespace Drupal\log;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the log type entity type.
 *
 * @see \Drupal\log\Entity\LogType
 */
class LogTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view log');
        break;

      case 'delete':
        return parent::checkAccess($entity, $operation, $account)->cacheUntilEntityChanges($entity);
        break;

      default:
        return parent::checkAccess($entity, $operation, $account);
        break;
    }
  }

}
