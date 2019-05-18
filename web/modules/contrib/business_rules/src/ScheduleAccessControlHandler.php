<?php

namespace Drupal\business_rules;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Schedule entity.
 *
 * @see \Drupal\business_rules\Entity\Schedule.
 */
class ScheduleAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\business_rules\Entity\ScheduleInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view schedule entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit schedule entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete schedule entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add schedule entities');
  }

}
