<?php

namespace Drupal\content_synchronizer;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Export entity entity.
 *
 * @see \Drupal\content_synchronizer\Entity\ExportEntity.
 */
class ExportEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\content_synchronizer\Entity\ExportEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished export entity entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published export entity entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit export entity entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete export entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add export entity entities');
  }

}
