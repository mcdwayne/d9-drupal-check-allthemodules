<?php

namespace Drupal\cbo_organization;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the organization entity type.
 *
 * @see \Drupal\cbo_organization\Entity\Organization
 */
class OrganizationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $organization, $operation, AccountInterface $account) {
    if ($operation == 'view') {
      return AccessResult::allowedIfHasPermission($account, 'access organization');
    }
    else {
      return parent::checkAccess($organization, $operation, $account);
    }
  }

}
