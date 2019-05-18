<?php

namespace Drupal\box;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Box entity.
 *
 * @see \Drupal\box\Entity\Box.
 */
class BoxAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $entity_bundle = $entity->bundle();

    /** @var \Drupal\box\Entity\BoxInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished box entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published box entities');

      case 'update':
        if ($account->hasPermission("edit any {$entity_bundle} box")) {
          return AccessResult::allowed();
        }
        if ($entity->getOwnerId() == $account->id()) {
          return AccessResult::allowedIfHasPermission($account, "edit own {$entity_bundle} box");
        }
        return AccessResult::forbidden();

      case 'delete':
        if ($account->hasPermission("delete any {$entity_bundle} box")) {
          return AccessResult::allowed();
        }
        if ($entity->getOwnerId() == $account->id()) {
          return AccessResult::allowedIfHasPermission($account, "delete own {$entity_bundle} box");
        }
        return AccessResult::forbidden();
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, "create {$entity_bundle} box");
  }

}
