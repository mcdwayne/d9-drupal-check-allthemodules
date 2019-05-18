<?php

namespace Drupal\client_config_care;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Config blocker entity entity.
 *
 * @see \Drupal\client_config_care\Entity\ConfigBlockerEntity.
 */
class ConfigBlockerEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\client_config_care\Entity\ConfigBlockerEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished config blocker entity entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published config blocker entity entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit config blocker entity entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete config blocker entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add config blocker entity entities');
  }

}
