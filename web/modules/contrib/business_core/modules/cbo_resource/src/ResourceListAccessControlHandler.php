<?php

namespace Drupal\cbo_resource;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the resource_list entity type.
 *
 * @see \Drupal\cbo_resource\Entity\ResourceList
 */
class ResourceListAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $resource_list, $operation, AccountInterface $account) {
    if ($operation == 'view') {
      return AccessResult::allowedIfHasPermission($account, 'access resource list');
    }
    else {
      return parent::checkAccess($resource_list, $operation, $account);
    }
  }

}
