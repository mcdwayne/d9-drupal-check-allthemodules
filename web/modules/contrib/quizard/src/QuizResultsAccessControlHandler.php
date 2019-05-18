<?php

namespace Drupal\quizard;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Quiz results entity.
 *
 * @see \Drupal\quizard\Entity\QuizResults.
 */
class QuizResultsAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\quizard\QuizResultsInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished quiz results entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published quiz results entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit quiz results entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete quiz results entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add quiz results entities');
  }

}
