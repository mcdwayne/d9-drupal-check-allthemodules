<?php

namespace Drupal\linkback;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Linkback entity.
 *
 * @see \Drupal\linkback\Entity\Linkback.
 */
class LinkbackAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\linkback\LinkbackInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished linkback entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published linkback entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit linkback entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete linkback entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add linkback entities');
  }

}
