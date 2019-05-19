<?php

namespace Drupal\transaction_workflow\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\transaction\TransactionTypeInterface;

/**
 * Checks that transaction type is workflow.
 */
class TransactionWorkflowTypeAccess implements AccessInterface {

  /**
   * Check that transaction type is workflow.
   *
   * @param \Drupal\transaction\TransactionTypeInterface $transaction_type
   *   The transaction type.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Allowed if the transaction type is workflow.
   */
  public function access(TransactionTypeInterface $transaction_type = NULL) {
    if (!$transaction_type) {
      return AccessResult::forbidden();
    }

    $result = $transaction_type->getPluginId() == 'transaction_workflow'
      ? AccessResult::allowed()
      : AccessResult::forbidden();

    return $result
      ->addCacheableDependency($transaction_type);
  }

}
