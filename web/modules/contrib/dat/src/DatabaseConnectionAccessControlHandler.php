<?php

namespace Drupal\dat;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the DatabaseConnection entity.
 *
 * @see \Drupal\dat\Entity\DatabaseConnection.
 */
class DatabaseConnectionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\dat\Entity\DatabaseConnectionInterface $entity */
    switch ($operation) {
      case 'update':
      case 'clone':
        return AccessResult::allowedIfHasPermission($account, 'edit dat connections');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete dat connections');

      case 'adminer':
        return AccessResult::allowedIfHasPermission($account, 'use adminer to manage the ' . $entity->id() . ' database');

      case 'editor':
        return AccessResult::allowedIfHasPermission($account, 'use editor to manage the ' . $entity->id() . ' database');

      case 'frame':
        return AccessResult::allowedIfHasPermission($account, 'use adminer to manage the ' . $entity->id() . ' database')
          ->orIf(AccessResult::allowedIfHasPermission($account, 'use editor to manage the ' . $entity->id() . ' database'));

      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }

}
