<?php


namespace Drupal\chatroom;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access control handler for the feed entity.
 *
 * @see \Drupal\chatroom\Entity\Chatroom
 */
class ChatroomAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $chatroom, $operation, AccountInterface $account) {
    $account = $this->prepareUser($account);

    // Possible $operation values: view, edit, delete, post.
    // (The first 3 are standard entity operations, and 'post' is a custom
    // operation to check access to post in chatrooms.)
    if ($operation == 'view') {
      // 'view any chatrooms' takes precedence, and owners can always view
      // their chatrooms.
      if ($account->id() == $chatroom->uid->entity->id() || $account->hasPermission('view any chatrooms')) {
        return AccessResult::allowed();
      }

      // Lack of 'view chatrooms' permission disallows viewing any chatroom.
      if (!$account->hasPermission('view chatrooms')) {
        return AccessResult::forbidden();
      }

      // Check roles selected in the chatroom.
      foreach ($chatroom->getViewRoles() as $rid) {
        if (in_array($rid, $account->getRoles())) {
          return AccessResult::allowed();
        }
      }

      return AccessResult::forbidden();
    }
    else if ($operation == 'edit') {
      if ($account->hasPermission('edit any chatrooms') || ($account->hasPermission('edit own chatrooms') && $chatroom->uid->entity->id() == $account->id())) {
        return AccessResult::allowed();
      }

      return AccessResult::forbidden();
    }
    else if ($operation == 'delete') {
      if ($account->hasPermission('delete any chatrooms') || ($account->hasPermission('delete own chatrooms') && $chatroom->uid->entity->id() == $account->id())) {
        return AccessResult::allowed();
      }

      return AccessResult::forbidden();
    }
    else if ($operation == 'post') {
      // 'post to any chatrooms' takes precedence.
      if ($account->hasPermission('post to any chatrooms')) {
        return AccessResult::allowed();
      }

      // Lack of 'post to chatrooms' permission disallows posting to any chatroom.
      if (!$account->hasPermission('post to chatrooms')) {
        return AccessResult::forbidden();
      }

      // Check roles selected in the chatroom.
      foreach ($chatroom->getPostRoles() as $rid) {
        if (in_array($rid, $account->getRoles())) {
          return AccessResult::allowed();
        }
      }

      return AccessResult::forbidden();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create chatrooms');
  }

}
