<?php

namespace Drupal\balance_tracker;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * The balance tracker service that keeps track of the user balances.
 */
class BalanceTrackerStorage {

  /**
   * The database connection.
   * 
   * @var \Drupal\Core\Database\Database
   */
  protected $database;

  /**
   * The module handler service.
   * 
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  public function __construct(Connection $database, ModuleHandlerInterface $module_handler) {
    $this->database = $database;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Adds a credit to a user's account.
   *
   * @param int $uid
   *   The UID of the user the credit should be applied to.
   * @param float $amount
   *   The amount to credit the account with.
   * @param string $message
   *   A string explaining the nature of the credit. (ie, 'Commission for $27
   *   purchase')
   *
   * @return bool
   *   A boolean reflecting whether the transaction was successful.
   */
  public function creditAccount($uid, $amount, $message) {
    return $this->createEntry($uid, 'credit', $amount, $message);
  }

  /**
   * Debits a user's account.
   *
   * @param int $uid
   *   The UID of the user the debit should be applied to.
   * @param float $amount
   *   The amount to debit the account.
   * @param string $message
   *   A string explaining the nature of the debit. (ie, 'Monthly account fees')
   *
   * @return bool
   *   A boolean reflecting whether the transaction was successful.
   */
  public function debitAccount($uid, $amount, $message) {
    return $this->createEntry($uid, 'debit', $amount, $message);
  }

  /**
   * Creates a balance entry in storage for a given user.
   *
   * @param int $uid
   *   The ID of the user for which a new entry is created.
   * @param string $type
   *   The type of entry - either 'credit' or 'debit'.
   * @param float $amount
   *   The amount to be credited or debited.
   * @param string $message
   *   The message that describes the entry.
   *
   * @return bool
   * @throws \Exception
   */
  public function createEntry($uid, $type, $amount, $message) {
    $balance = $this->getBalance($uid);

    // Invoke any hook_balance_prewrite() Implements.
    $this->moduleHandler->invokeAll('balance_prewrite', [$uid, $type, $amount, $message]);

    if ($type == 'credit') {
      $balance += (float) $amount;
    }
    else {
      $balance -= (float) $amount;
    }
    $fields = array(
      'uid' => $uid,
      'timestamp' => $_SERVER['REQUEST_TIME'],
      'type' => $type,
      'message' => $message,
      'amount' => $amount,
      'balance' => $balance,
    );

    $this->database->insert('balance_items')->fields($fields)->execute();

    $this->moduleHandler->invokeAll('balance_write', [$uid, $type, $amount, $message]);

    // Invalidate cache tags.
    Cache::invalidateTags(['balance_tracker:user:' . $uid, 'balance_tracker']);

    return TRUE;
  }

  /**
   * Gets a user's current balance.
   *
   * @param int $uid
   *   The UID of the user for whom the balance should be retrieved.
   *
   * @return float
   *   A float containing the balance of the user. If the user has no entries
   */
  public function getBalance($uid) {
    $result = $this->database->select('balance_items', 'bi')
      ->fields('bi', array('balance'))
      ->condition('uid', $uid, '=')
      ->orderBy('bi.bid', 'DESC')
      ->execute()
      ->fetchObject();

    if ($result !== FALSE) {
      return (float) $result->balance;
    }
    else {
      return 0.00;
    }
  }

  /**
   * Gets all items on the user's balance sheet.
   *
   * @param int $uid
   *   The UID of the user for whom the balance sheet should be retrieved.
   * @param int $per_page
   *   The number of entries that should be displayed on a page. Defaults
   *   to 25.
   *
   * @return array
   *   An array of balance sheet entries.
   */
  public function getItems($uid, $per_page = 25) {
    return $this->getItemsRange($uid, $per_page, 0, REQUEST_TIME);
  }

  /**
   * Gets all items on the user's balance sheet for a period between two timestamps.
   *
   * @param int $uid
   *   The UID of the user for whom the balance sheet should be retrieved.
   * @param int $per_page
   *   The number of entries that should be displayed on a page. Defaults
   *   to 25.
   * @param int $from
   *   The timestamp marking the beginning of the period for which we are retrieving balance items.
   * @param int $to
   *   The timestamp marking the end of the period for which we are retrieving balance items.
   *
   * @return array
   *   An array of balance sheet entries in chronological order.
   */
  public function getItemsRange($uid, $per_page = 25, $from = 0, $to = REQUEST_TIME) {
    $rows = [];
    $query = $this->database->select('balance_items', 'b')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');
    $results = $query
      ->fields('b', ['timestamp', 'message', 'type', 'amount', 'balance'])
      ->condition('uid', $uid)
      ->condition('timestamp', $from, '>=')
      ->condition('timestamp', $to, '<=')
      ->limit($per_page)
      ->orderBy('bid', 'DESC')
      ->execute();

    foreach ($results as $row) {
      $rows[] = $row;
    }

    return $rows;
  }

  /**
   * Returns the balances for all users.
   *
   * This should be used in conjunction with a pager to get the desired results.
   *
   * @param int $per_page
   *   The number of results to return per page.
   *
   * @return array
   */
  public function getAllUserBalances($per_page = 25) {
    $query = $this->database->select('balance_items', 'b1')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');
    $query->addField('b1', 'uid', 'uid');
    $query->addExpression('(SELECT b.balance FROM {balance_items} b
                            WHERE b.uid = b1.uid
                            ORDER BY b.bid DESC LIMIT 0,1)', 'balance');
    $query->groupBy('b1.uid');
    $query->limit($per_page);
    $results = $query->execute();

    $rows = [];
    foreach ($results as $row) {
      $rows[] = $row;
    }

    return $rows;
  }

}
