<?php

namespace Drupal\colossal_menu;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Menu entity.
 *
 * @see \Drupal\colossal_menu\Entity\Menu.
 */
class MenuAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\colossal_menu\LinkInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isEnabled()) {
          return AccessResult::allowedIfHasPermission($account, 'view disabled colossal_menu');
        }
        return AccessResult::allowedIfHasPermission($account, 'view enabled colossal_menu');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit colossal_menu');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete colossal_menu');
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add colossal_menu');
  }

}
