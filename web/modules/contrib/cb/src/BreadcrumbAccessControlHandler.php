<?php

namespace Drupal\cb;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the chained breadcrumb.
 *
 * @see \Drupal\cb\Entity\Breadcrumb
 */
class BreadcrumbAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'update':
        return AccessResult::allowedIfHasPermissions($account, ["edit chained breadcrumbs", 'administer cb'], 'OR');

      case 'delete':
        return AccessResult::allowedIfHasPermissions($account, ["delete chained breadcrumbs", 'administer cb'], 'OR');

      default:
        // No opinion.
        return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer cb');
  }

}
