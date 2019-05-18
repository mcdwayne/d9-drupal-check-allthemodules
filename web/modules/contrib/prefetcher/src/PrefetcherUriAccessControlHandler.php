<?php

namespace Drupal\prefetcher;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Prefetcher uri entity.
 *
 * @see \Drupal\prefetcher\Entity\PrefetcherUri.
 */
class PrefetcherUriAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\prefetcher\Entity\PrefetcherUriInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished prefetcher uri entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published prefetcher uri entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit prefetcher uri entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete prefetcher uri entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add prefetcher uri entities');
  }

}
