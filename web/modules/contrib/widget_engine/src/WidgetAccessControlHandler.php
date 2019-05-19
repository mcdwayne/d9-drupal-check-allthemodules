<?php

namespace Drupal\widget_engine;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Widget entity.
 *
 * @see \Drupal\widget_engine\Entity\Widget.
 */
class WidgetAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\widget_engine\Entity\WidgetInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished widget entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published widget entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit widget entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete widget entities');

      default:
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add widget entities');
  }

}
