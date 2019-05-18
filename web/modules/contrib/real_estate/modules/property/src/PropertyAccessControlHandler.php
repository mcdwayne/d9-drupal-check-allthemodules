<?php

namespace Drupal\real_estate_property;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Property entity.
 *
 * @see \Drupal\real_estate_property\Entity\Property.
 */
class PropertyAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\real_estate_property\Entity\PropertyInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished real estate property');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published real estate property');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit real estate property');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete real estate property');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add real estate property');
  }

}
