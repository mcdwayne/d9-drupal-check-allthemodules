<?php

namespace Drupal\uom;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the uom entity type.
 *
 * @see \Drupal\uom\Entity\Uom
 */
class UomAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $uom, $operation, AccountInterface $account) {
    if ($operation == 'view') {
      return AccessResult::allowed();
    }
    else {
      return parent::checkAccess($uom, $operation, $account);
    }
  }

}
