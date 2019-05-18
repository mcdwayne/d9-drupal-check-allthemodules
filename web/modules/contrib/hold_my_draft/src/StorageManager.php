<?php

namespace Drupal\hold_my_draft;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Driver\Exception\Exception;

/**
 * Class StorageManager.
 *
 * @package Drupal\hold_my_draft
 */
class StorageManager extends ControllerBase {

  /**
   * The Drupal database service.
   *
   * @var \Drupal\Core\Database\Database
   */
  private $connection;

  /**
   * The drupal time service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  private $time;

  /**
   * Draft-hold logger service.
   *
   * @var \Drupal\hold_my_draft\Logger
   */
  private $logger;

  /**
   * StorageManager constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The Drupal database service.
   * @param \Drupal\Component\Datetime\Time $time
   *   The Drupal time service.
   * @param \Drupal\hold_my_draft\Logger $logger
   *   The draft-hold logger service.
   */
  public function __construct(Connection $connection, Time $time, Logger $logger) {
    $this->connection = $connection;
    $this->time = $time;
    $this->logger = $logger;
  }

  /**
   * Create an initial draft-hold entry.
   *
   * @param int $nid
   *   Expects a node id.
   * @param int $uid
   *   Expects a user id.
   * @param int $vid_start
   *   Expects a revision id, the current default.
   * @param int $vid_hold
   *   Expects a revision id, the current latest.
   * @param string $status
   *   Optional status, defaults to 'In progress'.
   *
   * @throws \Exception
   */
  public function init(int $nid, int $uid, int $vid_start, int $vid_hold, string $status = 'In progress') {
    $this->connection->startTransaction();

    try {
      $this->connection->insert('hold_my_draft')->fields([
        'nid' => $nid,
        'uid' => $uid,
        'status' => $status,
        'start_latest_revision' => $vid_hold,
        'start_default_revision' => $vid_start,
        'hold_time' => $this->time->getCurrentTime(),
      ])->execute();
    }
    catch (Exception $exception) {
      $this->connection->rollBack();

      $this->logger->setError($exception->getMessage());
    }

  }

  /**
   * Returns progress of the latest draft-hold for a node, if present.
   *
   * @param int $nid
   *   Expects a node id.
   *
   * @return \Drupal\Core\Database\StatementInterface|null
   *   The query results.
   */
  public function getDraftHoldInformation(int $nid) {

    $this->connection->startTransaction();
    try {
      $query = $this->connection->select('hold_my_draft', 'hold');
      $query->condition('nid', $nid);
      $query->orderBy('hold_time', 'DESC');
      $query->fields('hold', [
        'status',
        'hold_time',
        'start_default_revision',
        'start_latest_revision',
        'uid',
      ]);
      $query->range(0, 1);
      $result = $query->execute()->fetchAll();

      return $result;
    }
    catch (Exception $exception) {
      $this->connection->rollBack();
      $this->logger->setError($exception->getMessage());
    }
  }

  /**
   * Finish a draft-hold and update its information.
   *
   * @param int $nid
   *   Expects a node id.
   * @param bool $revert
   *   Describes if the draft revision was reverted.
   */
  public function conclude(int $nid, bool $revert) {
    if ($revert) {
      $status = 'Complete - reverted';
    }
    else {
      $status = 'Complete - no reversion';
    }

    $this->connection->startTransaction();

    try {
      $this->connection->update('hold_my_draft')
        ->fields([
          'status' => $status,
          'hold_time' => $this->time->getCurrentTime(),
        ])
        ->condition('nid', $nid)
        ->condition('status', 'Complete', 'NOT LIKE')
        ->execute();
    }
    catch (Exception $exception) {
      $this->connection->rollBack();
      $this->logger->setError($exception->getMessage());
    }
  }

  /**
   * A theoretical fail catcher.
   *
   * This will fire if a draft-hold was started while another
   * was already in progress. This should not normally be possible.
   * If it does happen, mark time of death, give appropriate status,
   * cry to watchdog.
   *
   * @param int $nid
   *   Expects a node id.
   */
  public function abandon(int $nid) {
    $this->logger->setError(
      $this->t('A new draft-hold was started while one was already in progress. 
      This happened on /node/@id', [
        '@id' => __toString($nid),
      ])
    );

    $this->connection->startTransaction();

    try {
      $this->connection->update('hold_my_draft')
        ->fields([
          'status' => 'Abandoned',
          'hold_time' => $this->time->getCurrentTime(),
        ])
        ->condition('nid', $nid)
        ->condition('status', 'In progress')
        ->execute();
    }
    catch (Exception $exception) {
      $this->connection->rollBack();
      $this->logger->setError($exception->getMessage());
    }
  }

}
