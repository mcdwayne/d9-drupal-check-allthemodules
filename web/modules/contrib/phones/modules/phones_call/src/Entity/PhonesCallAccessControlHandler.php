<?php

namespace Drupal\phones_call\Entity;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Phones call entity.
 *
 * @see \Drupal\phones_call\Entity\PhonesCall.
 */
class PhonesCallAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\phones_call\Entity\PhonesCallInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished phones call entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published phones call entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit phones call entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete phones call entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add phones call entities');
  }

}
