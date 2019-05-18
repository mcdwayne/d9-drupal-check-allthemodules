<?php

namespace Drupal\country_state_field;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Country entity.
 *
 * @see \Drupal\country_state_field\Entity\Country.
 */
class CountryAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\country_state_field\Entity\CountryInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished country entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published country entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit country entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete country entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add country entities');
  }

}
