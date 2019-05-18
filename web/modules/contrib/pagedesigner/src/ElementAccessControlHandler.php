<?php

namespace Drupal\pagedesigner;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Pagedesigner Element entity.
 *
 * @see \Drupal\pagedesigner\Entity\Element.
 */
class ElementAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\pagedesigner\Entity\ElementInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished pagedesigner element entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published pagedesigner element entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit pagedesigner element entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete pagedesigner element entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add pagedesigner element entities');
  }

}
