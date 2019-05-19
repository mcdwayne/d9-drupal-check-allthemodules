<?php

namespace Drupal\splashify;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Splashify entity entity.
 *
 * @see \Drupal\splashify\Entity\SplashifyEntity.
 */
class SplashifyEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\splashify\Entity\SplashifyEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished Splashify entity entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published Splashify entity entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit Splashify entity entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete Splashify entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add Splashify entity entities');
  }

}
