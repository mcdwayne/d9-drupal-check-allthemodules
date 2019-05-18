<?php

namespace Drupal\cbo_inventory;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the subinventory entity type.
 *
 * @see \Drupal\cbo_inventory\Entity\Subinventory
 */
class SubinventoryAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $subinventory, $operation, AccountInterface $account) {
    if ($operation == 'view') {
      return AccessResult::allowedIfHasPermission($account, 'access subinventory');
    }
    else {
      return parent::checkAccess($subinventory, $operation, $account);
    }
  }

}
