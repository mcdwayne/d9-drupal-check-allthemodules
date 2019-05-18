<?php

namespace Drupal\ansible;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Ansible entity entity.
 *
 * @see \Drupal\ansible\Entity\AnsibleEntity.
 */
class AnsibleEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ansible\Entity\AnsibleEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished ansible entity entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published ansible entity entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit ansible entity entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete ansible entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ansible entity entities');
  }

}
