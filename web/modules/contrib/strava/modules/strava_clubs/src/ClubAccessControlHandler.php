<?php

namespace Drupal\strava_clubs;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Club entity.
 *
 * @see \Drupal\strava_clubs\Entity\Club.
 */
class ClubAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\strava_clubs\Entity\ClubInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished club entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published club entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit club entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete club entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add club entities');
  }

}
