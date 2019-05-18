<?php

namespace Drupal\media_reference_revisions;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Media reference revision entity.
 *
 * @see \Drupal\media_reference_revisions\Entity\MediaReferenceRevision.
 */
class MediaReferenceRevisionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\media_reference_revisions\Entity\MediaReferenceRevisionInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished media reference revision entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published media reference revision entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit media reference revision entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete media reference revision entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add media reference revision entities');
  }

}
