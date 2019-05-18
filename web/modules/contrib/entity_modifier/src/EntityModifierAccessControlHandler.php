<?php

namespace Drupal\entity_modifier;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Entity modifier entity.
 *
 * @see \Drupal\entity_modifier\Entity\EntityModifier.
 */
class EntityModifierAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\entity_modifier\Entity\EntityModifierInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished entity modifier entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published entity modifier entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit entity modifier entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete entity modifier entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add entity modifier entities');
  }

}
