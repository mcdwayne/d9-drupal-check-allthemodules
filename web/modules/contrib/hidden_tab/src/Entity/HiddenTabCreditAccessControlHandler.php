<?php

namespace Drupal\hidden_tab\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the hidden tab credit entity type.
 */
class HiddenTabCreditAccessControlHandler extends EntityAccessControlHandler {

  private const SIMPLE_OP_PERM = [
    HiddenTabCreditInterface::OP_UPDATE => HiddenTabCreditInterface::PERMISSION_UPDATE,
    HiddenTabCreditInterface::OP_DELETE => HiddenTabCreditInterface::PERMISSION_DELETE,
    HiddenTabCreditInterface::OP_VIEW => HiddenTabCreditInterface::PERMISSION_VIEW,
    HiddenTabCreditInterface::PERMISSION_UPDATE => HiddenTabCreditInterface::PERMISSION_UPDATE,
    HiddenTabCreditInterface::PERMISSION_DELETE => HiddenTabCreditInterface::PERMISSION_DELETE,
    HiddenTabCreditInterface::PERMISSION_VIEW => HiddenTabCreditInterface::PERMISSION_VIEW,
  ];

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    $admin = AccessResult::allowedIfHasPermission($account, HiddenTabCreditInterface::PERMISSION_ADMINISTER);
    if ($admin->isAllowed()) {
      return $admin;
    }
    if (!isset(self::SIMPLE_OP_PERM[$operation])) {
      return AccessResult::forbidden('unsupported operation');
    }
    return AccessResult::allowedIfHasPermission($account, self::SIMPLE_OP_PERM[$operation]);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      HiddenTabCreditInterface::PERMISSION_ADMINISTER,
      HiddenTabCreditInterface::PERMISSION_CREATE,
    ], 'OR');
  }

}
