<?php

namespace Drupal\assembly;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Assembly entity.
 *
 * @see \Drupal\assembly\Entity\Assembly.
 */
class AssemblyAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\assembly\Entity\AssemblyInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished assembly entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published assembly entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit assembly entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete assembly entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add assembly entities');
  }

}
