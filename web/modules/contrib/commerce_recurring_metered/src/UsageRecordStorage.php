<?php

namespace Drupal\commerce_recurring_metered;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_recurring\BillingPeriod;
use Drupal\commerce_recurring\Entity\SubscriptionInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides the default database storage backend for usage records.
 */
class UsageRecordStorage implements UsageRecordStorageInterface {

  /**
   * The table name to query from.
   *
   * @var string
   */
  protected $tableName = 'commerce_recurring_usage';

  /**
   * The usage record class to use.
   *
   * @var string
   */
  protected $recordClass = UsageRecord::class;

  /**
   * The database connection in use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $typeManager;

  /**
   * Constructs the usage record storage.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection for usage record storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $type_manager
   *   The entity type manager service.
   * @param string $recordClass
   *   The fully-qualified name of the record class to be used.
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $type_manager, $recordClass = NULL) {
    $this->connection = $connection;
    $this->typeManager = $type_manager;
    if (isset($recordClass)) {
      $this->recordClass = $recordClass;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fetchPeriodRecords(SubscriptionInterface $subscription, BillingPeriod $period, $usage_type = 'counter', ProductVariationInterface $variation = NULL) {
    // Usage type needs to be set. Complain if they've passed in a non-string.
    if (!is_string($usage_type)) {
      throw new \InvalidArgumentException('$usage_type must be a string.');
    }

    $query = $this->connection->select($this->tableName);
    $query->fields($this->tableName);
    $query->condition('usage_type', $usage_type);
    if (isset($variation)) {
      $query->condition('product_variation_id', $variation->id());
    }
    if ($subscription !== NULL) {
      $query->condition('subscription_id', $subscription->id());
    }
    if ($period !== NULL) {
      // To accurately get all records, we need to find any that overlap with
      // the time period of the billing period.
      $start = $period->getStartDate();
      $end = $period->getEndDate();

      // Since some usage records have no end, we need to search for any which
      // end later than the period's start date or have no end.
      $ends = $query->orConditionGroup()
        ->condition('end', $start->format('U'), '>')
        ->isNull('end');

      // Combine that with a condition to find those which start earlier than
      // the period's end date and we have everything we need.
      $timing = $query->andConditionGroup()
        ->condition('start', $end->format('U'), '<')
        ->condition($ends);

      // Et voila.
      $query->condition($timing);
    }

    $query->addTag('commerce_recurring_usage');

    $results = $query->execute();

    if ($results !== NULL) {
      return $this->createFromStorage($results);
    }

    return [];
  }

  /**
   * Factory method for turning raw records (from the database) into objects.
   *
   * @param \Drupal\Core\Database\StatementInterface $results
   *   The statement that will fetch the records.
   *
   * @return \Drupal\commerce_recurring_metered\UsageRecordInterface[]
   *   The usage record objects.
   */
  public function createFromStorage(StatementInterface $results) {
    $records = [];
    foreach ($results as $result) {
      $records[$result->usage_id] = new $this->recordClass($this->typeManager, $result);
    }

    return $records;
  }

  /**
   * Create a new usage record object shell.
   *
   * This injects the type manager service so the record can use it to fetch
   * stuff.
   */
  public function createRecord() {
    $recordClass = $this->recordClass;
    // Syntax is a pain.
    return new $recordClass($this->typeManager);
  }

  /**
   * Insert a usage record.
   *
   * @param UsageRecordInterface[] $records
   *   An array of records to insert or update.
   *
   * @throws \Exception
   */
  public function setRecords(array $records) {
    $txn = $this->connection->startTransaction();

    $inserts = [];
    $updates = [];
    foreach ($records as $record) {
      if ($record->getId()) {
        // Records which already have an ID must be updated.
        $updates[] = $record->getDatabaseValues();
      }
      else {
        $inserts[] = $record->getDatabaseValues();
      }
    }

    try {
      if (!empty($updates)) {
        foreach ($updates as $update) {
          $count = $this->connection->update($this->tableName)
            ->fields($update)
            ->condition('usage_id', $update['usage_id'])
            ->execute();

          // The number of rows matched had damn well better be 1.
          if ($count !== 1) {
            throw new \LogicException("Failed to update usage record $update[usage_id].");
          }
        }
      }

      if (!empty($inserts)) {
        $query = $this->connection->insert($this->tableName);
        foreach ($inserts as $insert) {
          $query->fields($insert);
        }

        $query->execute();
      }
    }
    catch (\Exception $e) {
      // Roll this back.
      $txn->rollBack();
      throw $e;
    }
  }

  /**
   * Delete one or more usage records.
   *
   * @param \Drupal\commerce_recurring_metered\UsageRecordInterface[] $records
   *   The usage records to be deleted.
   *
   * @throws \Exception
   */
  public function deleteRecords(array $records) {
    $txn = $this->connection->startTransaction();

    try {
      // Delete each record.
      foreach ($records as $record) {
        if ($record->getId()) {
          $this->connection->delete($this->tableName)
            ->condition('usage_id', $record->getId())
            ->execute();
        }
      }
    }
    catch (\Exception $e) {
      $txn->rollback();
      throw $e;
    }

    // We're done. Yay.
  }

}
