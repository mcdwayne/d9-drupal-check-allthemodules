<?php

namespace Drupal\chat_channels;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Chat channel member entity.
 *
 * @see \Drupal\chat_channels\Entity\ChatChannelMember.
 */
class ChatChannelMemberAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\chat_channels\Entity\ChatChannelInterface $entity */
    switch ($operation) {
      case 'update':
        return $this->checkUpdateAccess($account, array(), NULL);

      case 'delete':
        return $this->checkDeleteAccess($account, array(), NULL);

      case 'view':
        return $this->checkViewAccess($account, array(), NULL);
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add chat channel member entities');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkDeleteAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'delete chat channel member entities');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkUpdateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'edit chat channel member entities');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkViewAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'view channel member entities');
  }

}
