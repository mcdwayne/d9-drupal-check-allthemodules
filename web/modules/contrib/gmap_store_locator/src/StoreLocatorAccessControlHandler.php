<?php

namespace Drupal\store_locator;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Store locator entity.
 *
 * @see \Drupal\store_locator\Entity\StoreLocator.
 */
class StoreLocatorAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished store locator entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published store locator entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit store locator entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete store locator entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add store locator entities');
  }

}
