<?php

/**
 * @file
 * Contains \Drupal\cronpub\CronpubEntityAccessControlHandler.
 */

namespace Drupal\cronpub;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Cronpub Task entity.
 *
 * @see \Drupal\cronpub\Entity\CronpubEntity.
 */
class CronpubEntityAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view cronpub task entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit cronpub task entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete cronpub task entities');
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add cronpub task entities');
  }

}
