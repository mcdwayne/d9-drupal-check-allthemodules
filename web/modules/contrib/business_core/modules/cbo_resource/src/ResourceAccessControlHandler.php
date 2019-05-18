<?php

namespace Drupal\cbo_resource;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the resource entity type.
 *
 * @see \Drupal\cbo_resource\Entity\Resource
 */
class ResourceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $resource, $operation, AccountInterface $account) {
    if ($operation == 'view') {
      return AccessResult::allowedIfHasPermission($account, 'access resource');
    }
    else {
      return parent::checkAccess($resource, $operation, $account);
    }
  }

}
