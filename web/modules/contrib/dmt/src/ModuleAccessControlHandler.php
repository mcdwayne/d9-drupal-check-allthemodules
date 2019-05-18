<?php

namespace Drupal\dmt;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Module entity.
 *
 * @see \Drupal\dmt\Entity\Module.
 */
class ModuleAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\dmt\Entity\ModuleInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished module entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published module entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit module entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete module entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add module entities');
  }

}
