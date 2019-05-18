<?php

namespace Drupal\rocketship;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Rocketship Feed entity.
 *
 * @see \Drupal\rocketship\Entity\Feed.
 */
class FeedAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\rocketship\Entity\FeedInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::forbidden();

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit rocketship feed entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete rocketship feed entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add rocketship feed entities');
  }

}
