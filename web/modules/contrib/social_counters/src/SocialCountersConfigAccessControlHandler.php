<?php

/**
 * @file
 * Contains \Drupal\social_counters\SocialCountersConfigAccessControlHandler
 */

namespace Drupal\social_counters;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the social_counters entity.
 */
class SocialCountersConfigAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
      case 'edit':
      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer social counters');
    }
    return AccessResult::allowed();
  }
}
