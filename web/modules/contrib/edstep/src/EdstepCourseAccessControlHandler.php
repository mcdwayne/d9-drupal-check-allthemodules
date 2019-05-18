<?php

namespace Drupal\edstep;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the edstep term entity type.
 *
 * @see \Drupal\edstep\Entity\EdstepCourse
 */
class EdstepCourseAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if($account->hasPermission('administer edstep courses')) {
      return AccessResult::allowed();
    }
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view edstep courses');

      case 'delete':
        if($account->id() == $entity->getOwnerId()) {
          return AccessResult::allowedIfHasPermission($account, 'remove own edstep courses');
        }

      default:
        // No opinion.
        return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add edstep courses');
  }

}
