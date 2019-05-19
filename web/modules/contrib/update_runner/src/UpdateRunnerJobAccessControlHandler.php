<?php

namespace Drupal\update_runner;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the updata runner job entity.
 *
 * @see \Drupal\update_runner\Entity\ScheduledSiteUpdate.
 */
class UpdateRunnerJobAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\update_runner\Entity\UpdateRunnerJobInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished automatic updates job entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published automatic updates job entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit automatic updates job entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete automatic updates job entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add automatic updates job entities');
  }

}
