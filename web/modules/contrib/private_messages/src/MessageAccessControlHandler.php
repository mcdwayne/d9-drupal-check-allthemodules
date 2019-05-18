<?php

namespace Drupal\private_messages;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Message entity.
 *
 * @see \Drupal\private_messages\Entity\Message.
 */
class MessageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\private_messages\Entity\MessageInterface $entity */
    switch ($operation) {
      case 'view':
      case 'delete':
//        $recipient_id = $entity->get('recipient_uid')->getValue();
//        $sender_uid = $entity->get('user_id')->getValue();
//        $sender_uid = $sender_uid[0]['value'];

        if ($account->hasPermission('administer messages')) {
          return AccessResult::allowedIfHasPermission($account, 'administer messages');
        }
//        if (($recipient_id == $account->id()) || ($sender_uid == $account->id()) || ($account->id() == 1)
//        ) {
//          return AccessResult::allowedIfHasPermission($account, 'use messages');
//        }
//        else {
          return AccessResult::allowedIfHasPermission($account, 'administer messages');
//        }
        break;
      case 'update':
        if ($account->hasPermission('administer messages')) {
          return AccessResult::allowed();
        }
        if ($account->hasPermission('use messages')) {
          return AccessResult::forbidden();
        }
        break;

      default:
        return AccessResult::allowedIfHasPermission($account, 'administer messages');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, [
      'use messages',
      'administer messages'
    ], 'OR')->cachePerPermissions();
  }

}
