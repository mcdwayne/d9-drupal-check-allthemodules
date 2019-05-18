<?php

namespace Drupal\country_state_field;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the City entity.
 *
 * @see \Drupal\country_state_field\Entity\City.
 */
class CityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\country_state_field\Entity\CityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished city entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published city entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit city entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete city entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add city entities');
  }

}
