<?php

namespace Drupal\prepared_data\Storage;

use Drupal\prepared_data\PreparedData;
use Drupal\prepared_data\PreparedDataInterface;

/**
 * The SQL implementation for the storage of prepared data.
 */
class SqlStorage extends CachingStorageBase {

  use SqlStorageTrait;
  use ShorthandStorageTrait;

  /**
   * The table which stores prepared data.
   *
   * @var string
   */
  static protected $table = 'prepared_data';

  /**
   * Whether to use Gzip compression for storing data or not.
   *
   * @var bool
   */
  protected $useCompression;

  /**
   * SqlStorage constructor.
   */
  public function __construct() {
    $this->useCompression = function_exists('gzcompress') && function_exists('gzuncompress');
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoad($key) {
    try {
      return $this->doRead($key);
    }
    catch (\Exception $e) {
      // If there was an exception, try to create the table.
      if ($this->ensureTableExists()) {
        return $this->doRead($key);
      }
      // Some other failure that we can not recover from.
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($key, PreparedDataInterface $data) {
    try {
      return $this->doWrite($key, $data);
    }
    catch (\Exception $e) {
      // If there was an exception, try to create the table.
      if ($this->ensureTableExists()) {
        return $this->doWrite($key, $data);
      }
      // Some other failure that we can not recover from.
      throw $e;
    }
  }

  /**
   * Reads the data from the database table.
   *
   * @param string $key
   *   The data key to load the data for.
   *
   * @return \Drupal\prepared_data\PreparedDataInterface|null
   *   The prepared data if found, NULL otherwise.
   */
  protected function doRead($key) {
    $db = $this->getDatabase();
    $query = $db->select(static::$table, 'pd')->fields('pd', ['v', 'updated', 'expires', 'refresh']);
    $query->range(0, 1);
    $query->where('pd.k = :k', [':k' => $key]);
    $row = $query->execute()->fetchAssoc();
    if (!empty($row)) {
      $value = $this->useCompression ? gzuncompress($row['v']) : $row['v'];
      // PreparedData cares for lazy deserialization.
      return new PreparedData($value, $key, $row['updated'], $row['expires'], (bool) !$row['refresh']);
    }
    return NULL;
  }

  /**
   * Writes the data into the database table.
   *
   * By writing the whole data record into the database,
   * it will be unlocked for refreshing.
   *
   * @param string $key
   *   The data key to save the data for.
   * @param \Drupal\prepared_data\PreparedDataInterface $data
   *   The prepared data to save.
   */
  protected function doWrite($key, PreparedDataInterface $data) {
    $value = $this->useCompression ? gzcompress($data->encode()) : $data->encode();
    $db = $this->getDatabase();
    $db->upsert(static::$table)
      ->key('k')
      ->fields(['k', 'v', 'expires', 'updated', 'refresh', 'locked'], [$key, $value, $data->expires(), time(), (int) !$data->shouldRefresh(), 0])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function doFetchNext() {
    $db = $this->getDatabase();
    // Start transaction which reads and locks the fetched record,
    // to prevent other running processes from fetching the same record.
    $transaction = $db->startTransaction();
    try {
      if (!($next = $this->doReadNext())) {
        return NULL;
      }
      $this->doLockNext($next);
      return $next;
    }
    catch (\Exception $e) {
      // If there was an exception, try to create the table.
      if ($this->ensureTableExists()) {
        if (!($next = $this->doReadNext())) {
          return NULL;
        }
        $this->doLockNext($next);
        return $next;
      }
      // Some other failure that we can not recover from.
      $transaction->rollBack();
      throw $e;
    }
  }

  /**
   * Method component of ::doFetchNext() to read the next record.
   *
   * @return \Drupal\prepared_data\PreparedDataInterface|null
   *   The fetched prepared data record.
   */
  protected function doReadNext() {
    $db = $this->getDatabase();
    $query = $db->select(static::$table, 'pd')->fields('pd', ['k', 'v', 'updated', 'expires', 'refresh']);
    $query->range(0, 1);
    $query->where('pd.locked < :locked', [':locked' => time() + 900]);
    $query->orderBy('pd.refresh', 'ASC');
    $query->orderBy('pd.expires', 'ASC');
    $row = $query->execute()->fetchAssoc();
    if (!empty($row)) {
      $value = $this->useCompression ? gzuncompress($row['v']) : $row['v'];
      // PreparedData cares for lazy deserialization.
      return new PreparedData($value, $row['k'], $row['updated'], $row['expires'], (bool) !$row['refresh']);
    }
    return NULL;
  }

  /**
   * Method component of ::doFetchNext() to lock the fetched record.
   *
   * @param \Drupal\prepared_data\PreparedDataInterface $data
   *   The fetched prepared data record.
   */
  protected function doLockNext(PreparedDataInterface $data) {
    $db = $this->getDatabase();
    $db->update(static::$table)->fields(['locked' => time()])
      ->where('k = :key', [':key' => $data->key()])->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($key) {
    $db = $this->getDatabase();
    $delete = $db->delete(static::$table)->where('k = :key', [':key' => $key]);
    try {
      $this->getShorthandStorage()->deleteFor($key);
      return $delete->execute();
    }
    catch (\Exception $e) {
      if ($this->ensureTableExists()) {
        return $delete->execute();
      }
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function flagToRefresh(array $keys = []) {
    $db = $this->getDatabase();
    $update = $db->update(static::$table)->fields(['refresh' => 0]);
    if (!empty($keys)) {
      $update = $update->where('k IN (:keys[])', [':keys[]' => $keys]);
    }
    try {
      return $update->execute();
    }
    catch (\Exception $e) {
      if ($this->ensureTableExists()) {
        return $update->execute();
      }
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function schemaDefinition() {
    $schema = [
      'description' => 'The table for storing prepared data.',
      'fields' => [
        'k' => [
          'description' => 'Prepared data identifier.',
          'type' => 'varchar',
          'length' => 187,
          'not null' => TRUE,
        ],
        'v' => [
          'description' => 'Encoded prepared data, eventually compressed.',
          'type' => 'blob',
          'size' => 'big',
          'not null' => FALSE,
        ],
        'refresh' => [
          'description' => 'Whether this data should be refreshed (0) or not (1).',
          'type' => 'int',
          'size' => 'tiny',
          'default' => 1,
          'not null' => TRUE,
        ],
        'expires' => [
          'description' => 'The timestamp of validness expiration.',
          'type' => 'int',
          'not null' => TRUE,
        ],
        'updated' => [
          'description' => 'The timestamp of the last data update.',
          'type' => 'int',
          'not null' => TRUE,
        ],
        'locked' => [
          'description' => 'Whether the record is being processed (timestamp of lock) or not (0).',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ],
      ],
      'indexes' => [
        'refex' => ['refresh', 'expires'],
      ],
      'primary key' => ['k'],
    ];
    return $schema;
  }

}
