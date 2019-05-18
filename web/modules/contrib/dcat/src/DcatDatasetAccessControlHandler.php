<?php

namespace Drupal\dcat;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Dataset entity.
 *
 * @see \Drupal\dcat\Entity\DcatDataset.
 */
class DcatDatasetAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\dcat\Entity\DcatDatasetInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished dataset entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published dataset entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit dataset entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete dataset entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add dataset entities');
  }

}
