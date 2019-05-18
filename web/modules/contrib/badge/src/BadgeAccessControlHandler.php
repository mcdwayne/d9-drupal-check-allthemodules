<?php

namespace Drupal\badge;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Badge entity.
 *
 * @see \Drupal\badge\Entity\Badge.
 */
class BadgeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\badge\Entity\BadgeInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished badge entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published badge entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit badge entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete badge entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add badge entities');
  }

}
