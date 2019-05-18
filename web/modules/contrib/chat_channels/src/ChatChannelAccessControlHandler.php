<?php

namespace Drupal\chat_channels;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Chat channel entity.
 *
 * @see \Drupal\chat_channels\Entity\ChatChannel.
 */
class ChatChannelAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\chat_channels\Entity\ChatChannelInterface $entity */
    switch ($operation) {
      case 'view':
        return $this->checkViewAccess($entity, $account, array(), NULL);

      case 'update':
        return $this->checkUpdateAccess($account, array(), NULL);

      case 'delete':
        return $this->checkDeleteAccess($account, array(), NULL);
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add chat channel entities');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkDeleteAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'delete chat channel entities');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkUpdateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'edit chat channel entities');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkViewAccess(EntityInterface $entity, AccountInterface $account, array $context, $entity_bundle = NULL) {
    /** @var \Drupal\chat_channels\Entity\ChatChannelInterface $entity */
    if (!$entity->isActive()) {
      return AccessResult::allowedIfHasPermission($account, 'view inactive chat channel entities');
    }
    return AccessResult::allowedIfHasPermission($account, 'view active chat channel entities');
  }

}
