<?php

namespace Drupal\entity_reference_uuid_test;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Test entity one entity.
 *
 * @see \Drupal\entity_reference_uuid_test\Entity\TestEntityOne.
 */
class TestEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        if (!$entity->get('status')) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished entity_reference_uuid test entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published entity_reference_uuid test entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit entity_reference_uuid test entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete entity_reference_uuid test entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add entity_reference_uuid test entities');
  }

}
