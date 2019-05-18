<?php

namespace Drupal\chatbot;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Workflow entity.
 *
 * @see \Drupal\chatbot\Entity\Workflow.
 */
class WorkflowAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\chatbot\Entity\WorkflowInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished workflow entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published workflow entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit workflow entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete workflow entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add workflow entities');
  }

}
