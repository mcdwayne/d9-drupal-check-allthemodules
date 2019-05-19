<?php

namespace Drupal\webform_cart;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Webform cart order entity entity.
 *
 * @see \Drupal\webform_cart\Entity\WebformCartOrder.
 */
class WebformCartOrderAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\webform_cart\Entity\WebformCartOrderInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished webform cart order entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published webform cart order entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit webform cart order entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete webform cart order entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add webform cart order entities');
  }

}
