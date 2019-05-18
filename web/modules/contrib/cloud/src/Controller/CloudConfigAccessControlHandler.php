<?php

namespace Drupal\cloud\Controller;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Cloud config entity.
 *
 * @see \Drupal\cloud\Entity\CloudConfig.
 */
class CloudConfigAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\cloud\Entity\CloudConfigInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          if ($account->id() == $entity->getOwnerId()) {
            $permissions = ['view own unpublished cloud config entities'];
          }
          else {
            $permissions = ['view unpublished cloud config entities', 'view ' . $entity->getCloudContext()];
          }
          return AccessResult::allowedIfHasPermissions($account, $permissions);
        }

        if ($account->id() == $entity->getOwnerId()) {
          $permissions = ['view own published cloud config entities'];
        }
        else {
          $permissions = ['view published cloud config entities', 'view ' . $entity->getCloudContext()];
        }
        return AccessResult::allowedIfHasPermissions($account, $permissions);

      case 'update':
        if ($account->id() == $entity->getOwnerId()) {
          $permissions = ['edit own cloud config entities'];
        }
        else {
          $permissions = ['edit cloud config entities', 'view ' . $entity->getCloudContext()];
        }
        return AccessResult::allowedIfHasPermissions($account, $permissions);

      case 'delete':
        if ($account->id() == $entity->getOwnerId()) {
          $permissions = ['delete own cloud config entities'];
        }
        else {
          $permissions = ['delete cloud config entities', 'view ' . $entity->getCloudContext()];
        }
        return AccessResult::allowedIfHasPermissions($account, $permissions);
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add cloud config entities');
  }

}
