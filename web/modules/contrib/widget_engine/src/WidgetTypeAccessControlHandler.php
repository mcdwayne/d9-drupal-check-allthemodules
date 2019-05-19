<?php

namespace Drupal\widget_engine;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the widget type entity type.
 *
 * @see \Drupal\widget_engine\Entity\WidgetType
 */
class WidgetTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view published widget entities');

      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }

}
