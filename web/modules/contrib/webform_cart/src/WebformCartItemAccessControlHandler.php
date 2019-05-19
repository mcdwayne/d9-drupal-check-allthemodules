<?php

namespace Drupal\webform_cart;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Webform cart item entity entity.
 *
 * @see \Drupal\webform_cart\Entity\WebformCartItem.
 */
class WebformCartItemAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\webform_cart\Entity\WebformCartItemInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished webform cart item entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published webform cart item entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit webform cart item entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete webform cart item entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add webform cart item entities');
  }

}
