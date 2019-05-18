<?php

namespace Drupal\custom_messages;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Custom Message entity.
 *
 * @see \Drupal\custom_messages\Entity\CustomMessage.
 */
class CustomMessageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\custom_messages\Entity\CustomMessageInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished custom message entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published custom message entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit custom message entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete custom message entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add custom message entities');
  }

}
