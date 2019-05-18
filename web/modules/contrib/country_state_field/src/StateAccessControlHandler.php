<?php

namespace Drupal\country_state_field;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the State entity.
 *
 * @see \Drupal\country_state_field\Entity\State.
 */
class StateAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\country_state_field\Entity\StateInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished state entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published state entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit state entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete state entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add state entities');
  }

}
