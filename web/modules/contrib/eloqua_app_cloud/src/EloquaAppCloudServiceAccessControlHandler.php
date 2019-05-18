<?php

namespace Drupal\eloqua_app_cloud;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Eloqua AppCloud Service entity.
 *
 * @see \Drupal\eloqua_app_cloud\Entity\EloquaAppCloudService.
 */
class EloquaAppCloudServiceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\eloqua_app_cloud\Entity\EloquaAppCloudServiceInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished eloqua appcloud service entities');
        }

        return AccessResult::allowed();

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit eloqua appcloud service entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete eloqua appcloud service entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add eloqua appcloud service entities');
  }

}
