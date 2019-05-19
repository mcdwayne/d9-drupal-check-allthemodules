<?php

namespace Drupal\subscription_entity;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Subscription Term entity.
 *
 * @see \Drupal\subscription_entity\Entity\subscription_term.
 */
class SubscriptionTermAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\subscription_entity\Entity\subscription_termInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished subscription term entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published subscription term entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit subscription term entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete subscription term entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add subscription term entities');
  }

}
