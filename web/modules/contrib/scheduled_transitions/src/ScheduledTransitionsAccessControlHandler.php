<?php

declare(strict_types = 1);

namespace Drupal\scheduled_transitions;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for scheduled transitions.
 */
class ScheduledTransitionsAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    /** @var \Drupal\scheduled_transitions\Entity\ScheduledTransitionInterface $entity */
    $access = parent::checkAccess($entity, $operation, $account);

    if ($access->isNeutral()) {
      $entity = $entity->getEntity();
      if ($entity) {
        // Defer access to associated entity.
        return $entity->access($operation, $account, TRUE);
      }
    }

    return $access;
  }

}
