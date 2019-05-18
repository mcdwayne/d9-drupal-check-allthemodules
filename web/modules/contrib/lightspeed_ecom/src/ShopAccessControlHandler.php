<?php

namespace Drupal\lightspeed_ecom;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the Shop entity type.
 *
 * @see Drupal\lightspeed_ecom\Entity\Shop
 */
class ShopAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'delete':
        // Prevent delete of the `default` Shop.
        if ($entity->id() == ShopInterface::DEFAULT_ID) {
          return AccessResult::forbidden();
        }

      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }

}
