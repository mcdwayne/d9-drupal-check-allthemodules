<?php

namespace Drupal\flashpoint_course_content;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\flashpoint_course\FlashpointCourseUtilities;

/**
 * Access controller for the Flashpoint course content entity.
 *
 * @see \Drupal\flashpoint_course_content\Entity\FlashpointCourseContent.
 */
class FlashpointCourseContentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\flashpoint_course_content\Entity\FlashpointCourseContentInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished flashpoint course content entities');
        }
        $open = FlashpointCourseUtilities::isOpenAccessCourse($entity->getCourse());
        if ($open) {
          return AccessResult::allowed();
        }
        return AccessResult::allowedIfHasPermission($account, 'view published flashpoint course content entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit flashpoint course content entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete flashpoint course content entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add flashpoint course content entities');
  }

}
