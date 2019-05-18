<?php

namespace Drupal\monster_menus;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\monster_menus\Controller\DefaultController;

/**
 * Access controller for the MM Page entity.
 *
 * @see \Drupal\monster_menus\Entity\MMTree.
 */
class MMTreeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\monster_menus\Entity\MMTree $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIf(mm_content_user_can($entity->id(), Constants::MM_PERMS_READ, $account));

      case 'update':
        return DefaultController::menuAccessEdit($entity, $account);

      case 'delete':
        return DefaultController::menuAccessDelete($entity, $account);
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add mm page entities');
  }

}
