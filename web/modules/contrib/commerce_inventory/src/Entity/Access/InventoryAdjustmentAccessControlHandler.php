<?php

namespace Drupal\commerce_inventory\Entity\Access;

use Drupal\commerce_inventory\Entity\InventoryItemInterface;
use Drupal\commerce_inventory\Entity\InventoryLocationInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Inventory Adjustment entity.
 *
 * @see \Drupal\commerce_inventory\Entity\InventoryAdjustment.
 */
class InventoryAdjustmentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\commerce_inventory\Entity\InventoryAdjustmentInterface $entity */
    switch ($operation) {
      case 'view':
        return $entity->getLocation()->access($operation, $account, TRUE);

      case 'delete':
        return AccessResult::forbidden();
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if (array_key_exists('commerce_inventory_item', $context) && $context['commerce_inventory_item'] instanceof InventoryItemInterface) {
      $context['commerce_inventory_location'] = $context['commerce_inventory_item']->getLocation();
    }
    if (array_key_exists('commerce_inventory_location', $context) && $context['commerce_inventory_location'] instanceof InventoryLocationInterface) {
      return $context['commerce_inventory_location']->access('inventory_modify', $account, TRUE);
    }
    return AccessResult::allowedIfHasPermission($account, 'modify any commerce inventory location inventory');
  }

}
