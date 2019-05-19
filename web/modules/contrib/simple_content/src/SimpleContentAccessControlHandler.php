<?php

namespace Drupal\simple_content;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Simple content entity.
 *
 * @see \Drupal\simple_content\Entity\SimpleContent.
 */
class SimpleContentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\simple_content\Entity\SimpleContentInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished simple content entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published simple content entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit simple content entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete simple content entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add simple content entities');
  }

}
