<?php

namespace Drupal\commerce_inventory\Entity\Access;

use Drupal\commerce_inventory\Entity\InventoryLocationInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Inventory Item entity.
 *
 * @see \Drupal\commerce_inventory\Entity\InventoryItem.
 */
class InventoryItemAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $entity */

    // Check if user has access to modify inventory at the location.
    if ($entity->getLocation() instanceof InventoryLocationInterface) {
      $access_result = $entity->getLocation()->access('inventory_modify', $account, TRUE);
      if ($access_result->isAllowed()) {
        /** @var \Drupal\Core\Access\AccessResultAllowed $access_result */
        return $access_result->addCacheableDependency($entity);
      }
    }
    // Check if user has access to modify inventory at any location.
    return AccessResult::allowedIfHasPermission($account, 'modify any commerce inventory location inventory');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if (array_key_exists('commerce_inventory_location', $context)) {
      if ($context['commerce_inventory_location'] instanceof InventoryLocationInterface) {
        return $context['commerce_inventory_location']->access('inventory_modify', $account, TRUE);
      }
    }
    return AccessResult::allowedIfHasPermission($account, 'modify any commerce inventory location inventory');
  }

}
