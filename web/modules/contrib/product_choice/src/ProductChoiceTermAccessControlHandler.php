<?php

namespace Drupal\product_choice;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Product choice term entity.
 *
 * @see \Drupal\product_choice\Entity\ProductChoiceTerm.
 */
class ProductChoiceTermAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\product_choice\Entity\ProductChoiceTermInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'administer commerce_product_type');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit product choice terms');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'edit product choice terms');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'edit product choice terms');
  }

}
