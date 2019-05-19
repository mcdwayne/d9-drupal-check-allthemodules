<?php

namespace Drupal\visualn_dataset;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the VisualN Data Set entity.
 *
 * @see \Drupal\visualn_dataset\Entity\VisualNDataSet.
 */
class VisualNDataSetAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\visualn_dataset\Entity\VisualNDataSetInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished visualn data set entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published visualn data set entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit visualn data set entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete visualn data set entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add visualn data set entities');
  }

}
