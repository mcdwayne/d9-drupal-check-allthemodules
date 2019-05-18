<?php

namespace Drupal\filebrowser\Entity;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Filebrowser metadata entity entity.
 *
 * @see \Drupal\filebrowser\Entity\FilebrowserMetadataEntity.
 */
class FilebrowserMetadataEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\filebrowser\Entity\FilebrowserMetadataEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished filebrowser metadata entity entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published filebrowser metadata entity entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit filebrowser metadata entity entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete filebrowser metadata entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add filebrowser metadata entity entities');
  }

}