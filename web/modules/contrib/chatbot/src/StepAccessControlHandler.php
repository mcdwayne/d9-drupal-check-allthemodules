<?php

namespace Drupal\chatbot;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Step entity.
 *
 * @see \Drupal\chatbot\Entity\Step.
 */
class StepAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\chatbot\Entity\StepInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished step entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published step entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit step entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete step entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add step entities');
  }

}
