<?php

namespace Drupal\content_synchronizer;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Import entity.
 *
 * @see \Drupal\content_synchronizer\Entity\ImportEntity.
 */
class ImportEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\content_synchronizer\Entity\ImportEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished import entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published import entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit import entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete import entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add import entities');
  }

}
