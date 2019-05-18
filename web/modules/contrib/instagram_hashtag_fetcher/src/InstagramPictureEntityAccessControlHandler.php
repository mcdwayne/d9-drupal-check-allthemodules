<?php

namespace Drupal\instagram_hashtag_fetcher;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Instagram Picture Entity entity.
 *
 * @see \Drupal\instagram_pictures\Entity\InstagramPictureEntity.
 */
class InstagramPictureEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\instagram_pictures\Entity\InstagramPictureEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished instagram picture entity entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published instagram picture entity entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit instagram picture entity entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete instagram picture entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add instagram picture entity entities');
  }

}
