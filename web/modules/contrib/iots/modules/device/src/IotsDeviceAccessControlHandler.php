<?php

namespace Drupal\iots_device;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Device entity.
 *
 * @see \Drupal\iots_device\Entity\IotsDevice.
 */
class IotsDeviceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\iots_device\Entity\IotsDeviceInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished device entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published device entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit device entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete device entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add device entities');
  }

}
