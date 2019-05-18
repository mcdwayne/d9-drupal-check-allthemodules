<?php

namespace Drupal\real_estate_agency;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Agency entity.
 *
 * @see \Drupal\real_estate_agency\Entity\Agency.
 */
class AgencyAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\real_estate_agency\Entity\AgencyInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished agency entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published agency entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit agency entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete agency entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add agency entities');
  }

}
