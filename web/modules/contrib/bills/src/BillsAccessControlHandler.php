<?php

namespace Drupal\bills;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Bills entity.
 *
 * @see \Drupal\bills\Entity\Bills.
 */
class BillsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\bills\Entity\BillsInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished bills entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published bills entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit bills entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete bills entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add bills entities');
  }

}
