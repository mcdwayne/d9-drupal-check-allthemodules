<?php

namespace Drupal\ads_system;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Ad entity.
 *
 * @see \Drupal\ads_system\Entity\Ad.
 */
class AdAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ads_system\AdInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ad entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published ad entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit ad entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete ad entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ad entities');
  }

}
