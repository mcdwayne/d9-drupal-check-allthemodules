<?php

namespace Drupal\hidden_tab\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\hidden_tab\Utility;

/**
 * Defines the access control handler for the hidden tab mailer entity type.
 */
class HiddenTabMailerAccessControlHandler extends EntityAccessControlHandler {

  const SIMPLE_OP_PERM = [
    HiddenTabMailerInterface::OP_UPDATE => HiddenTabMailerInterface::PERMISSION_UPDATE,
    HiddenTabMailerInterface::OP_DELETE => HiddenTabMailerInterface::PERMISSION_DELETE,
    HiddenTabMailerInterface::OP_VIEW => HiddenTabMailerInterface::PERMISSION_VIEW,
    HiddenTabMailerInterface::PERMISSION_UPDATE => HiddenTabMailerInterface::PERMISSION_UPDATE,
    HiddenTabMailerInterface::PERMISSION_DELETE => HiddenTabMailerInterface::PERMISSION_DELETE,
    HiddenTabMailerInterface::PERMISSION_VIEW => HiddenTabMailerInterface::PERMISSION_VIEW,
  ];

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    $admin = AccessResult::allowedIfHasPermission($account, HiddenTabMailerInterface::PERMISSION_ADMINISTER);
    if ($admin->isAllowed()) {
      return $admin;
    }
    if (!isset(self::SIMPLE_OP_PERM[$operation])) {
      return AccessResult::forbidden('unsupported operation');
    }
    return AccessResult::allowedIfHasPermissions($account, [
      HiddenTabMailerInterface::PERMISSION_ADMINISTER,
      self::SIMPLE_OP_PERM[$operation],
    ], 'OR');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      HiddenTabMailerInterface::PERMISSION_ADMINISTER,
      HiddenTabMailerInterface::PERMISSION_CREATE,
    ], 'OR');
  }

}
