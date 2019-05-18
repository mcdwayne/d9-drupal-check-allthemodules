<?php

namespace Drupal\chat_channels;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Chat channel message entity.
 *
 * @see \Drupal\chat_channels\Entity\ChatChannelMessage.
 */
class ChatChannelMessageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\chat_channels\Entity\ChatChannelMessageInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished chat channel message entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published chat channel message entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit chat channel message entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete chat channel message entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add chat channel message entities');
  }

}
