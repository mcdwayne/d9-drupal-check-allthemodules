<?php

namespace Drupal\item_lot;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the item_lot entity type.
 *
 * @see \Drupal\item_lot\Entity\ItemLot
 */
class ItemLotAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $item_lot, $operation, AccountInterface $account) {
    /** @var \Drupal\item_lot\ItemLotInterface $item_lot */
    if ($item = $item_lot->getItem()) {
      switch ($operation) {
        case 'view':
        case 'update':
          return $item->access($operation, $account, TRUE);

        case 'delete':
          return $item->access('update', $account, TRUE);
      }
    }

    return parent::checkAccess($item_lot, $operation, $account);
  }

}
