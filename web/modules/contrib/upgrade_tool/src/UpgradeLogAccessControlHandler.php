<?php

namespace Drupal\upgrade_tool;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Upgrade log entity.
 *
 * @see \Drupal\upgrade_tool\Entity\UpgradeLog.
 */
class UpgradeLogAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\upgrade_tool\Entity\UpgradeLogInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view upgrade log entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit upgrade log entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete upgrade log entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add upgrade log entities');
  }

}
