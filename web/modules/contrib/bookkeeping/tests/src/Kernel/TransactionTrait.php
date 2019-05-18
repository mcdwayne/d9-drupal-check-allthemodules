<?php

namespace Drupal\Tests\bookkeeping\Kernel;

/**
 * Trait to help with transaction related tests.
 *
 * @package Drupal\Tests\bookkeeping\Kernel
 */
trait TransactionTrait {

  /**
   * The bookkeeping transaction storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $transactionStorage;

  /**
   * Assert the number of new transactions since we last checked.
   *
   * @param int $expected
   *   The expected number of new transactions.
   * @param string $message
   *   Optional message.
   */
  protected function assertNewTransactionsCount(int $expected, string $message = '') {
    $new_count = $this->transactionStorage
      ->getQuery()
      ->count()
      ->execute();

    $new_transactions = $new_count - $this->transactionCount;
    $this->assertSame($expected, $new_transactions, $message);
    $this->transactionCount = $new_count;
  }

  /**
   * Assert the number of new transactions since we last checked.
   *
   * @param array $expected
   *   Details of the new transactions. An array of arrays, each inner array
   *   can optionally contain any of:
   *   - generator: The expected generator.
   *   - description: The expected description.
   *   - entries: An array containing arrays of any of:
   *     - account: The account ID.
   *     - amount: The amount.
   *     - currency_code: The currency code.
   *     - type: The entry type.
   * @param string $message
   *   Message prefix for the assert.
   */
  protected function assertNewTransactionsDetail(array $expected, string $message) {
    $transactions = $this->getNewTransactions(count($expected));

    foreach ($expected as $delta => $expected_transaction) {
      $transaction = array_pop($transactions);

      if (isset($expected_transaction['generator'])) {
        $this->assertEqual($transaction->get('generator')->value, $expected_transaction['generator'], "$message: [$delta] generator.");
      }

      if (isset($expected_transaction['description'])) {
        $this->assertEqual($transaction->get('description')->value, $expected_transaction['description'], "$message: [$delta] description.");
      }

      if (isset($expected_transaction['entries'])) {
        foreach ($expected_transaction['entries'] as $entry_delta => $expected_entry) {
          /** @var \Drupal\bookkeeping\Plugin\Field\FieldType\BookkeepingEntryItem $entry */
          $entry = $transaction->get('entries')->get($entry_delta);

          if (isset($expected_entry['account'])) {
            $this->assertEqual($entry->target_id, $expected_entry['account'], "$message: [$delta] entry [$entry_delta] account.");
          }

          if (isset($expected_entry['amount'])) {
            $this->assertEqual($entry->amount, $expected_entry['amount'], "$message: [$delta] entry [$entry_delta] amount.");
          }

          if (isset($expected_entry['currency_code'])) {
            $this->assertEqual($entry->currency_code, $expected_entry['currency_code'], "$message: [$delta] entry [$entry_delta] currency.");
          }

          if (isset($expected_entry['type'])) {
            $this->assertEqual($entry->type, $expected_entry['type'], "$message: [$delta] entry [$entry_delta] type.");
          }
        }
      }
    }
  }

  /**
   * Get the newest transactions.
   *
   * @param int $count
   *   The number of transactions to get.
   *
   * @return \Drupal\bookkeeping\Entity\TransactionInterface[]
   *   The newest $count transactions.
   */
  protected function getNewTransactions(int $count = 1) {
    $new_transactions = $this->transactionStorage
      ->getQuery()
      ->sort('id', 'DESC')
      ->range(0, $count)
      ->execute();
    return $this->transactionStorage->loadMultiple($new_transactions);
  }

}
