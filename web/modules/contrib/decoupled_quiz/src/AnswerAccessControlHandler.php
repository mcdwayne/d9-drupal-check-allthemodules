<?php

namespace Drupal\decoupled_quiz;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Answer entity.
 *
 * @see \Drupal\decoupled_quiz\Entity\Answer.
 */
class AnswerAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\decoupled_quiz\Entity\AnswerInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished answer entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published answer entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit answer entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete answer entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add answer entities');
  }

}
