<?php

namespace Drupal\global_content;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Global Content entity.
 *
 * @see \Drupal\global_content\Entity\GlobalContent.
 */
class GlobalContentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\global_content\Entity\GlobalContentInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished global content entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published global content entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit global content entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete global content entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add global content entities');
  }

}
