<?php

namespace Drupal\sapi_data;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Statistics API Data entry entity.
 *
 * @see \Drupal\sapi_data\Entity\SAPIData.
 */
class SAPIDataAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\sapi_data\SAPIDataInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished sapi data entry entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published sapi data entry entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit sapi data entry entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete sapi data entry entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add sapi data entry entities');
  }

}
