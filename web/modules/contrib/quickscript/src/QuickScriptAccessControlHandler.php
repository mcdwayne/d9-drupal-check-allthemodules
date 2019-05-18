<?php

/**
 * @file
 * Contains \Drupal\quickscript\QuickScriptAccessControlHandler.
 */

namespace Drupal\quickscript;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Quick Script entity.
 *
 * @see \Drupal\quickscript\Entity\QuickScript.
 */
class QuickScriptAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\quickscript\QuickScriptInterface $entity */
    switch ($operation) {
      case 'view':
        return FALSE;

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit quick scripts');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete quick scripts');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add quick scripts');
  }

}
