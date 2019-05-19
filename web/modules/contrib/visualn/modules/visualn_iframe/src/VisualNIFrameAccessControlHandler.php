<?php

namespace Drupal\visualn_iframe;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

// @todo: check

/**
 * Access controller for the VisualN IFrame entity.
 *
 * @see \Drupal\visualn_iframe\Entity\VisualNIFrame.
 */
class VisualNIFrameAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\visualn_iframe\Entity\VisualNIFrameInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished visualn iframe entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published visualn iframe entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit visualn iframe entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete visualn iframe entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add visualn iframe entities');
  }

}
