<?php

namespace Drupal\menu_megadrop;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Menu megadrop entity.
 *
 * @see \Drupal\menu_megadrop\Entity\MenuMegadrop.
 */
class MenuMegadropAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\menu_megadrop\Entity\MenuMegadropInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished menu megadrop entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published menu megadrop entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit menu megadrop entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete menu megadrop entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add menu megadrop entities');
  }

}
