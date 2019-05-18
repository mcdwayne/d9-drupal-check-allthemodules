<?php

namespace Drupal\alexanders;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity\EntityAccessControlHandler;

/**
 * Controls access based on the Order entity permissions.
 */
class OrderAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $account = $this->prepareUser($account);
    // Unlocking an order requires the same permissions as 'update', with an
    // additional check to ensure that the order is actually locked.
    $additional_operation = '';
    if ($operation == 'unlock') {
      $operation = 'update';
      $additional_operation = 'unlock';
    }
    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = parent::checkAccess($entity, $operation, $account);

    /** @var \Drupal\alexanders\Entity\AlexandersOrderInterface $entity */
    if ($result->isNeutral() && $operation == 'view') {
      if ($account->id() == $entity->getCustomerId()) {
        $result = AccessResult::allowedIfHasPermissions($account, ['view own alexanders_order']);
        $result = $result->cachePerUser()->addCacheableDependency($entity);
      }
    }

    return $result;
  }

}
