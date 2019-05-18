<?php

namespace Drupal\entity_pilot\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access-controller for departure entities.
 */
class DepartureAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /* @var \Drupal\entity_pilot\FlightInterface $entity */
    if ($operation == 'approve') {
      // You can't approve a arrival that isn't pending.
      return AccessResult::allowedIf($account->hasPermission('administer entity_pilot departures') && $entity->isPending())
        ->cachePerPermissions();
    }
    if ($operation == 'queue') {
      // You can't queue a arrival that isn't ready.
      return AccessResult::allowedIf($account->hasPermission('administer entity_pilot departures') && $entity->isReady())
        ->cachePerPermissions();
    }
    if ($operation == 'update') {
      // You can't edit a arrival that is queued.
      return AccessResult::allowedIf($account->hasPermission('administer entity_pilot departures') && !$entity->isQueued())
        ->cachePerPermissions();
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}
