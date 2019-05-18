<?php

namespace Drupal\iots_channel;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Iots Channel entity.
 *
 * @see \Drupal\iots_channel\Entity\IotsChannel.
 */
class IotsChannelAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\iots_channel\Entity\IotsChannelInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished iots channel entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published iots channel entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit iots channel entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete iots channel entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add iots channel entities');
  }

}
