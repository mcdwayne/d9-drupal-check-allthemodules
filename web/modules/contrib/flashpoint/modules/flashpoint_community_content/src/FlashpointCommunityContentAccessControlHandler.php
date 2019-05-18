<?php

namespace Drupal\flashpoint_community_content;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Flashpoint community content entity.
 *
 * @see \Drupal\flashpoint_community_content\Entity\FlashpointCommunityContent.
 */
class FlashpointCommunityContentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\flashpoint_community_content\Entity\FlashpointCommunityContentInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished flashpoint community content entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published flashpoint community content entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit flashpoint community content entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete flashpoint community content entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add flashpoint community content entities');
  }

}
