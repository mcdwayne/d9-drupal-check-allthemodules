<?php

namespace Drupal\supplier;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the supplier entity type.
 *
 * @see \Drupal\supplier\Entity\Supplier
 */
class SupplierAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $supplier, $operation, AccountInterface $account) {
    if ($operation == 'view') {
      return AccessResult::allowedIfHasPermission($account, 'access supplier');
    }
    else {
      return parent::checkAccess($supplier, $operation, $account);
    }
  }

}
