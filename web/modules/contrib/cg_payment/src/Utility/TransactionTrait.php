<?php

namespace Drupal\cg_payment\Utility;

/**
 * Trait TransactionTrait, provides utility methods related to transactions.
 *
 * @package Drupal\cg_payment\Utility
 */
trait TransactionTrait {

  /**
   * Helper function to return transaction entity by remote ID value.
   *
   * @param string $remote_id
   *   The remote ID.
   * @param array $statuses
   *   List of statuses to filter by, defaults to pending only.
   *
   * @return \Drupal\cg_payment\TransactionInterface|bool
   *   Returns the transaction or false if could'nt find the transaction.
   */
  public static function getTransactionByRemoteId($remote_id, array $statuses = ['pending']) {
    // Search for the transaction by remote_id.
    $storage = \Drupal::entityTypeManager()->getStorage('transaction');
    $query = $storage->getQuery();
    $entity_id = $query
      ->condition('remote_id', $remote_id, '=')
      // Don't load transactions which are not in pending state.
      ->condition('status', $statuses, 'IN')
      ->range(0, 1)
      ->execute();

    if (!empty($entity_id)) {
      return $storage->load(reset($entity_id));
    }

    return FALSE;
  }

}
