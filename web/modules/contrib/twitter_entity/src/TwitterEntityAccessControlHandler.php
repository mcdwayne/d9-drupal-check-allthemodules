<?php

namespace Drupal\twitter_entity;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Twitter entity entity.
 *
 * @see \Drupal\twitter_entity\Entity\TwitterEntity.
 */
class TwitterEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\twitter_entity\Entity\TwitterEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished twitter entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published twitter entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit twitter entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete twitter entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add twitter entities');
  }

}
