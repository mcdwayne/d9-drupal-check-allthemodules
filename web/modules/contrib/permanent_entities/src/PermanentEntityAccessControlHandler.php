<?php

namespace Drupal\permanent_entities;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Permanent Entity entity.
 *
 * @see \Drupal\permanent_entities\Entity\PermanentEntity.
 */
class PermanentEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\permanent_entities\Entity\PermanentEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished permanent entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published permanent entities');

      case 'update':
        $edit_permissions = [
          'edit permanent entities of type ' . $entity->bundle(),
          'edit all permanent entities',
        ];
        return AccessResult::allowedIfHasPermissions($account, $edit_permissions, 'OR');

      case 'delete':
        return AccessResult::forbidden();
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::forbidden();
  }

}
