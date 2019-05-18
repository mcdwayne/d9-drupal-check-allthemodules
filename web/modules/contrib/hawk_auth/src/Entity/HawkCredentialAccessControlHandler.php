<?php

/**
 * @file
 * Contains \Drupal\hawk_auth\Entity\HawkCredentialAccessControlHandler.
 */

namespace Drupal\hawk_auth\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks for delete and create permission for individual credentials.
 */
class HawkCredentialAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(HawkCredentialInterface $entity, $operation, $langcode, AccountInterface $account) {
    if ($operation == 'edit' || $operation == 'delete') {
      $user = $entity->getOwner();

      return AccessResult::allowedIf(
        $account->hasPermission('administer hawk') ||
        ($account->hasPermission($operation . ' own hawk credentials') && $account->id() == $user->id())
      );
    }
    else {
      return parent::checKAccess($entity, $operation, $langcode, $account);
    }
  }

}
