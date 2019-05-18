<?php

namespace Drupal\config_selector\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the feature entity type.
 *
 * @see \Drupal\config_selector\Entity\Feature
 */
class FeatureAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
      case 'manage':
        return AccessResult::allowedIf($account->hasPermission('administer site configuration'))->cachePerPermissions();

      // Leave plumbing for editing and deleting via the UI.
      // @todo implement.
      case 'update':
      case 'delete':
        return AccessResult::forbidden();

      default:
        // No opinion.
        return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Leave plumbing for creating via the UI.
    // @todo implement.
    return AccessResult::forbidden();
  }

}
