<?php

namespace Drupal\flashpoint_course_module;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Course module entity.
 *
 * @see \Drupal\flashpoint_course_module\Entity\FlashpointCourseModule.
 */
class FlashpointCourseModuleAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\flashpoint_course_module\Entity\FlashpointCourseModuleInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished course module entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published course module entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit course module entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete course module entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add course module entities');
  }

}
