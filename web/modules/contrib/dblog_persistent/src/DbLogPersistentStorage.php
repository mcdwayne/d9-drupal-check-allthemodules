<?php

namespace Drupal\dblog_persistent;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseException;
use Drupal\dblog\Logger\DbLog;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\TableSortExtender;

class DbLogPersistentStorage implements DbLogPersistentStorageInterface {

  /**
   * The table name.
   *
   * @var string
   */
  protected static $table = 'dblog_persistent';

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Keep the distinct types in memory.
   *
   * @var string[]
   */
  private $types;

  /**
   * Keep the channel counts in memory.
   *
   * @var int[]
   */
  private $counts;

  /**
   * DbLogPersistentLoader constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   * @throws \Drupal\Core\Database\IntegrityConstraintViolationException
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   */
  public function getTypes(): array {
    if ($this->types === NULL) {
      $this->types = $this->database
        ->query('SELECT DISTINCT(`type`) FROM {dblog_persistent} ORDER BY `type`')
        ->fetchAllKeyed(0, 0);
    }
    return $this->types;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   * @throws \Drupal\Core\Database\IntegrityConstraintViolationException
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   */
  public function countChannel(string $channel): int {
    // Counting all channels at once saves some queries.
    if ($this->counts === NULL) {
      $this->counts = $this->database
        ->query('SELECT `channel`, COUNT(*) AS `count` FROM {dblog_persistent} GROUP BY `channel`')
        ->fetchAllKeyed();
    }
    return $this->counts[$channel] ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public function clearChannel(string $channel): int {
    return $this->database
      ->delete('dblog_persistent')
      ->condition('channel', $channel)
      ->execute();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \PDOException
   * @throws \Drupal\Core\Database\DatabaseException
   */
  public function writeLog(string $channel, array $fields): void {
    $fields['channel'] = $channel;

    try {
      $this->database
        ->insert(static::$table)
        ->fields($fields)
        ->execute();
    } catch (\Exception $e) {
      /**
       * @see \Drupal\dblog\Logger\DbLog::log().
       */
      if (
        // Only handle database related exceptions.
        ($e instanceof DatabaseException || $e instanceof \PDOException) &&
        // Avoid an endless loop of re-write attempts.
        $this->database->getTarget() !== DbLog::DEDICATED_DBLOG_CONNECTION_TARGET
      ) {
        // Open a dedicated connection for logging.
        $key = $this->database->getKey();
        $info = Database::getConnectionInfo($key);
        Database::addConnectionInfo($key, DbLog::DEDICATED_DBLOG_CONNECTION_TARGET, $info['default']);
        $this->database = Database::getConnection(DbLog::DEDICATED_DBLOG_CONNECTION_TARGET, $key);
        // Now try once to log the error again.
        $this->writeLog($channel, $fields);
      }
      else {
        throw $e;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getChannel(string $channel, int $count = NULL, array $header = NULL) {
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->database->select(static::$table, 'w')
      ->extend(PagerSelectExtender::class)
      ->extend(TableSortExtender::class);
    $query->fields('w', [
      'wid',
      'uid',
      'severity',
      'type',
      'timestamp',
      'message',
      'variables',
      'link',
    ]);
    $query->leftJoin('users_field_data', 'ufd', 'w.uid = ufd.uid');
    $query->condition('w.channel', $channel);

    if ($count) {
      /** @var $query PagerSelectExtender */
      $query = $query->limit(50);
    }
    if ($header) {
      /** @var $query TableSortExtender $query */
      $query = $query->orderByHeader($header);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   * @throws \Drupal\Core\Database\IntegrityConstraintViolationException
   * @throws \Drupal\Core\Database\DatabaseExceptionWrapper
   */
  public function getEvent(int $event_id) {
    return $this->database
      ->query('SELECT w.*, u.uid FROM {dblog_persistent} w LEFT JOIN {users} u ON u.uid = w.uid WHERE w.wid = :id', [':id' => $event_id])
      ->fetchObject();
  }

}
