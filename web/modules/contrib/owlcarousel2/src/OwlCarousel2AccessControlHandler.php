<?php

namespace Drupal\owlcarousel2;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the OwlCarousel2 entity.
 *
 * @see \Drupal\owlcarousel2\Entity\OwlCarousel2.
 */
class OwlCarousel2AccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\owlcarousel2\Entity\OwlCarousel2Interface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished owlcarousel2 entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published owlcarousel2 entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit owlcarousel2 entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete owlcarousel2 entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add owlcarousel2 entities');
  }

}
