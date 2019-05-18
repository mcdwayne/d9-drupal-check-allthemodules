<?php
/**
 * @file
 * Contains \Drupal\nodeletter\NodeletterSendingAccessControlHandler.
 */

namespace Drupal\nodeletter;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the nodeletter_sending entity.
 *
 * @see \Drupal\nodeletter\Entity\NodeletterSending.
 */
class NodeletterSendingAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view nodeletter_sending entity');

      case 'edit':
        return AccessResult::allowedIfHasPermission($account, 'edit nodeletter_sending entity');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete nodeletter_sending entity');
    }
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add nodeletter_sending entity');
  }

}
