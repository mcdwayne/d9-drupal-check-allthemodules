<?php

namespace Drupal\modal_config;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the modal config entity type.
 *
 * @see \Drupal\modal_config\Entity\ModalConfig
 */
class ModalConfigAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
      case 'update':
      case 'delete':
        return AccessResult::allowedIf($account->hasPermission('administer modal config'))->cachePerPermissions();

      default:
        // No opinion.
        return AccessResult::neutral();
    }
  }

}
