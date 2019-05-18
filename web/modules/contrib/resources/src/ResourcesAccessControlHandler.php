<?php

namespace Drupal\resources;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Resources entity.
 *
 * @see \Drupal\resources\Entity\Resources.
 */
class ResourcesAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\resources\Entity\ResourcesInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished resources entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published resources entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit resources entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete resources entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add resources entities');
  }

}
