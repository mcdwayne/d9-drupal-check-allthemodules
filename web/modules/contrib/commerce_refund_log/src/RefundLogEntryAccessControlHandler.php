<?php

namespace Drupal\commerce_refund_log;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for refund log entries.
 *
 * @see \Drupal\commerce_refund_log\Entity\RefundLogEntry
 */
class RefundLogEntryAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if (in_array($operation, ['update', 'delete'])) {
      return AccessResult::forbidden('Refund logs can not be deleted since refunds can not be refunded.');
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
