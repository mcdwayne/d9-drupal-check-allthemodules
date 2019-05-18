<?php

namespace Drupal\odoo_api_entity_sync;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\odoo_api_entity_sync\Event\SyncStatusSaveEvent;
use PDO;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Odoo sync mapping manager service.
 */
class MappingManager implements MappingManagerInterface {

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new MappingManager object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Time service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(Connection $database, TimeInterface $time, EventDispatcherInterface $event_dispatcher) {
    $this->database = $database;
    $this->eventDispatcher = $event_dispatcher;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function getSyncStatus($entity_type, $odoo_model, $export_type, $entity_id) {
    if (!is_array($entity_id)) {
      $entity_id = [$entity_id];
    }

    $status = $this
      ->database
      ->select('odoo_api_entity_sync', 's')
      ->fields('s', [
        'entity_id',
        'status',
        'odoo_id',
        'sync_time',
      ])
      ->condition('entity_type', $entity_type)
      ->condition('odoo_model', $odoo_model)
      ->condition('export_type', $export_type)
      ->condition('entity_id', $entity_id, 'IN')
      ->execute()
      ->fetchAllAssoc('entity_id', PDO::FETCH_ASSOC);

    return $status + array_fill_keys($entity_id, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getIdMap($entity_type, $odoo_model, $export_type, $entity_id) {
    if (!is_array($entity_id)) {
      $entity_id = [$entity_id];
    }

    $status = $this
      ->database
      ->select('odoo_api_entity_sync', 's')
      ->fields('s', [
        'entity_id',
        'odoo_id',
      ])
      ->condition('entity_type', $entity_type)
      ->condition('odoo_model', $odoo_model)
      ->condition('export_type', $export_type)
      ->condition('entity_id', $entity_id, 'IN')
      ->execute()
      ->fetchAllKeyed();

    return array_map('intval', $status) + array_fill_keys($entity_id, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function setSyncStatus($entity_type, $odoo_model, $export_type, array $id_map, $status, $cron_export = TRUE) {
    $this->assertStatusValue($status);

    foreach ($id_map as $entity_id => $odoo_id) {
      $fields = [
        'status' => $status,
        'cron_export' => $cron_export ? static::CRON_EXPORT_ENABLED : static::CRON_EXPORT_DISABLED,
      ];

      // Unset fail time by default.
      $fields['fail_time'] = 0;
      switch ($status) {
        case static::STATUS_SYNCED:
          $fields['sync_time'] = $this->time->getRequestTime();
          break;

        case static::STATUS_FAILED:
          // Update fail timestamp to move failed items to the end of the queue.
          $fields['fail_time'] = $this->time->getRequestTime();
          break;
      }
      if ($odoo_id !== NULL) {
        // Only set Odoo ID if it's not NULL.
        $fields['odoo_id'] = $odoo_id;
      }
      elseif ($status == static::STATUS_DELETED) {
        // Unset Odoo ID if object was deleted from Odoo.
        $fields['odoo_id'] = NULL;
      }

      $this
        ->database
        ->merge('odoo_api_entity_sync')
        ->keys([
          'entity_type' => $entity_type,
          'odoo_model' => $odoo_model,
          'export_type' => $export_type,
          'entity_id' => $entity_id,
        ])
        ->fields($fields)
        ->execute();
    }

    $this->eventDispatcher->dispatch(SyncStatusSaveEvent::STATUS_SAVE, new SyncStatusSaveEvent($entity_type, $odoo_model, $export_type, $id_map, $status, $cron_export));
  }

  /**
   * Asserts that sync status is correct.
   *
   * @param int $status
   *   New sync status. May be either STATUS_NOT_SYNCED or STATUS_SYNCED.
   */
  protected function assertStatusValue($status) {
    $valid_statuses = [
      static::STATUS_NOT_SYNCED,
      static::STATUS_IN_PROGRESS,
      static::STATUS_SYNCED,
      static::STATUS_FAILED,
      static::STATUS_SYNC_EXCLUDED,
      static::STATUS_DELETION_IN_PROGRESS,
      static::STATUS_DELETED,
      static::STATUS_ENTITY_LOAD_ERROR,
    ];

    if (!in_array($status, $valid_statuses)) {
      throw new \InvalidArgumentException('Incorrect sync status.');
    }
  }

  /**
   * Returns a query for fetching a sync queue.
   *
   * @param bool|null $cron_export
   *   Controls fetching items exported on cron.
   *   Possible values are:
   *
   *   - NULL: get all items (do not apply condition).
   *   - TRUE: get items which should be exported on cron.
   *   - FALSE: get all items excluded from cron export.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A database query object.
   */
  protected function getSyncQueueQuery($cron_export) {
    $query = $this
      ->database
      ->select('odoo_api_entity_sync', 's')
      ->fields('s', [
        'entity_type',
        'odoo_model',
        'export_type',
        'entity_id',
      ])
      // Move failed items to the end.
      ->orderBy('fail_time', 'ASC');

    if ($cron_export !== NULL) {
      $cron_export = $cron_export ? static::CRON_EXPORT_ENABLED : static::CRON_EXPORT_DISABLED;
      $query->condition('cron_export', $cron_export, '=');
    }

    $query->condition('status', [
      static::STATUS_SYNCED,
      static::STATUS_SYNC_EXCLUDED,
      static::STATUS_DELETED,
      static::STATUS_ENTITY_LOAD_ERROR,
    ], 'NOT IN');

    $fail_timestamp_condition = $query->orConditionGroup();
    /** @var \Drupal\Core\Database\Query\ConditionInterface $fail_timestamp_condition */
    $fail_timestamp_condition->condition('status', static::STATUS_FAILED, '<>')
      // @TODO: Move 1800 (30 minutes) to config.
      ->condition('fail_time', $this->time->getRequestTime() - 1800, '<=');

    // Exclude failures happened in last 30 minutes.
    $query->condition($fail_timestamp_condition);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function countSyncQueue($cron_export) {
    $query = $this->getSyncQueueQuery($cron_export);
    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getSyncQueue($limit, $cron_export) {
    $query = $this->getSyncQueueQuery($cron_export)->range(0, $limit);

    $queue = [];
    $result = $query->execute();
    while ($row = $result->fetchAssoc()) {
      $queue[$row['entity_type']][$row['odoo_model']][$row['export_type']][$row['entity_id']] = $row['entity_id'];
    }

    return $queue;
  }

  /**
   * {@inheritdoc}
   */
  public function findMappedEntities($odoo_model, array $odoo_ids) {
    $return = array_fill_keys($odoo_ids, FALSE);

    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $this
      ->database
      ->select('odoo_api_entity_sync', 's')
      ->fields('s', [
        'odoo_id',
        'entity_type',
        'export_type',
        'entity_id',
      ])
      ->condition('odoo_model', $odoo_model)
      ->condition('odoo_id', $odoo_ids, 'IN');

    $result = $query->execute();
    while ($row = $result->fetchObject()) {
      $return[$row->odoo_id][$row->entity_type][$row->export_type] = $row->entity_id;
    }

    return $return;
  }

}
