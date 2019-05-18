<?php

namespace Drupal\homebox;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Homebox Layout entity.
 *
 * @see \Drupal\homebox\Entity\HomeboxLayout.
 */
class HomeboxLayoutAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\homebox\Entity\HomeboxLayoutInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished homebox layout entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published homebox layout entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit homebox layout entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete homebox layout entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add homebox layout entities');
  }

}
