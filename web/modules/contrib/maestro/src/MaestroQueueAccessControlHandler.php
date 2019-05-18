<?php

namespace Drupal\maestro;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Maestro Queue entity.
 *
 */
class MaestroQueueAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    
    //return AccessResult::neutral();
    
    
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view maestro queue entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit maestro queue entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete maestro queue entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add maestro queue entities');
  }

}
