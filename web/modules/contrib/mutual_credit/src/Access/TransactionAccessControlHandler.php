<?php

namespace Drupal\mcapi\Access;

use Drupal\mcapi\TransactionOperations;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Defines an access controller option for the mcapi_transaction entity.
 */
class TransactionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $wids = \Drupal::entityQuery('mcapi_wallet')
      ->condition('holder_entity_type',  'user')
      ->condition('holder_entity_id',  $account->id())
      ->execute();
    if (empty($wids)) {
      return AccessResult::forbidden('Not enough wallets.');
    }
    $wids = \Drupal::entityQuery('mcapi_wallet')->count()->execute();
    if ($wids < 2) {
      return AccessResult::forbidden('Not enough wallets.');
    }
    return AccessResult::allowed()->cachePerUser();
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $transaction, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $account = $this->prepareUser($account);
    if ($operation == 'view label') {
      $operation = 'view';
    }
    if ($operation === 'view' && $account->hasPermission('view all transactions')) {
      // @todo URGENT. Handle the named payees and payers
      $result = AccessResult::allowed()->cachePerUser();
    }
    elseif ($action = TransactionOperations::loadOperation($operation)) {
      $result = $action->getPlugin()
        ->access($transaction, $account, TRUE)
        ->cachePerUser()
        ->addCacheableDependency($transaction);
    }
    else {
      $result = AccessResult::forbidden('This transaction is private.')->cachePerPermissions();
    }
    return $return_as_object ? $result: $result->isAllowed();
  }

}
