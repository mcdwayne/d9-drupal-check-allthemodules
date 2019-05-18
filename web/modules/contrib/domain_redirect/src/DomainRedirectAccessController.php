<?php

/**
 * @file
 * Contains \Drupal\domain_redirect\DomainRedirectAccessController.
 */

namespace Drupal\domain_redirect;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Defines an access controller for the domain redirect entity.
 */
class DomainRedirectAccessController extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Only users with the admin permission can create.
    return AccessResult::allowedIfHasPermission($account, $this->entityType->getAdminPermission());
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Only users with the admin permission can create.
    return AccessResult::allowedIfHasPermission($account, $this->entityType->getAdminPermission());
  }
}
