<?php

namespace Drupal\commerce_installments;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Installment Plan entity.
 *
 * @see \Drupal\commerce_installments\Entity\InstallmentPlan.
 */
class InstallmentPlanAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\commerce_installments\Entity\InstallmentPlanInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view installment plan entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit installment plan entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete installment plan entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add installment plan entities');
  }

}
