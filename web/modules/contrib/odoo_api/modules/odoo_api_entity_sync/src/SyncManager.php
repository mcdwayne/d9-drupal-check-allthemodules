<?php

namespace Drupal\odoo_api_entity_sync;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\Timer;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\odoo_api_entity_sync\Event\EntitySyncError;
use Drupal\odoo_api_entity_sync\Exception\EntityLockException;
use Drupal\odoo_api_entity_sync\Exception\ExportException;
use Drupal\odoo_api_entity_sync\Exception\RecursiveExportException;
use Drupal\odoo_api_entity_sync\Exception\RemovalRequestException;
use Drupal\odoo_api_entity_sync\Exception\SyncExcludedException;
use Drupal\odoo_api_entity_sync\Plugin\EntitySyncPluginManagerInterface;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class SyncManager.
 */
class SyncManager implements SyncManagerInterface {

  const CRON_TIMER = 'odoo_api_entity_sync_cron';

  /**
   * Mapping manager.
   *
   * @var \Drupal\odoo_api_entity_sync\MappingManagerInterface
   */
  protected $idMap;

  /**
   * Sync plugin manager.
   *
   * @var \Drupal\odoo_api_entity_sync\Plugin\EntitySyncPluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The log channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $log;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * IDs of entities which are sync pending.
   *
   * @var array
   */
  protected $delayedSyncIds = [];

  /**
   * Flag indicating whether the shutdown function is registered.
   *
   * @var bool
   */
  protected $shutdownFunctionRegistered;

  /**
   * Constructs a new SyncManager object.
   */
  public function __construct(MappingManagerInterface $map, EntitySyncPluginManagerInterface $plugin_manager, EntityTypeManagerInterface $entity_type_manager, LockBackendInterface $lock, LoggerChannelFactoryInterface $logger_factory, EventDispatcherInterface $event_dispatcher) {
    $this->idMap = $map;
    $this->pluginManager = $plugin_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->lock = $lock;
    $this->log = $logger_factory->get('odoo_api_entity_sync');
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function export($entity_type, $odoo_model, $export_type, $entity_id, $force_update_changed = FALSE) {
    if (!is_array($entity_id)) {
      $entity_id = [$entity_id];
    }

    $this->exportMissing($entity_type, $odoo_model, $export_type, $entity_id, $force_update_changed);
    return $this->idMap->getIdMap($entity_type, $odoo_model, $export_type, $entity_id);
  }

  /**
   * {@inheritdoc}
   */
  public function registerEntity($entity_type, $odoo_model, $export_type, $entity_ids) {
    // Make sure there's a plugin, fail early otherwise.
    $this->pluginManager->getInstanceByType($entity_type, $odoo_model, $export_type);
    $entity_ids = is_array($entity_ids) ? $entity_ids : [$entity_ids];

    // Update database, set sync status to NOT_SYNCED.
    $this
      ->idMap
      ->setSyncStatus($entity_type, $odoo_model, $export_type, array_fill_keys($entity_ids, NULL), MappingManagerInterface::STATUS_NOT_SYNCED, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function sync($entity_type, $odoo_model, $export_type, $entity_ids, $recursive_export = FALSE) {
    if (!is_array($entity_ids)) {
      $entity_ids = [$entity_ids];
    }
    elseif (empty($entity_ids)) {
      // Empty array, nothing to do.
      return [];
    }

    $found = [];

    $sync_plugin = $this->pluginManager->getInstanceByType($entity_type, $odoo_model, $export_type);
    $entities = $this->entityTypeManager->getStorage($entity_type)->loadMultiple($entity_ids);
    foreach ($entities as $entity) {
      $entity_id = $entity->id();
      $found[] = $entity_id;
      $lock_id = $this->getLockId($entity_type, $odoo_model, $export_type, $entity_id);

      if (!$this->lock->acquire($lock_id)) {
        // Could not acquire the lock.
        throw new EntityLockException($entity_type, $odoo_model, $export_type, $entity_id);
      }

      // Set sync status to 'in progress'.
      $this->idMap->setSyncStatus($entity_type, $odoo_model, $export_type, [$entity_id => NULL], MappingManagerInterface::STATUS_IN_PROGRESS);

      try {
        $sync_plugin
          ->assertShouldSync($entity);
      }
      catch (SyncExcludedException $e) {
        // This entity shouldn't be synced. It's okay if the export was called
        // directly, just skip.
        $this->idMap->setSyncStatus($entity_type, $odoo_model, $export_type, [$entity_id => NULL], MappingManagerInterface::STATUS_SYNC_EXCLUDED);
        $this->lock->release($lock_id);
        if ($recursive_export) {
          // Re-throw the same exception so that it could be caught by parent
          // entity sync process.
          throw $e;
        }
        continue;
      }
      catch (RemovalRequestException $e) {
        // The object is pending removal.
        // Some sync plugins may request if the object is a virtual entry.
        // Referring such item is not allowed.
        $this->idMap->setSyncStatus($entity_type, $odoo_model, $export_type, [$entity_id => NULL], MappingManagerInterface::STATUS_DELETION_IN_PROGRESS);
        // @TODO: Handle delete error.
        $sync_plugin
          ->assertEntity($entity)
          ->deleteFromOdoo($entity);
        $this->idMap->setSyncStatus($entity_type, $odoo_model, $export_type, [$entity_id => NULL], MappingManagerInterface::STATUS_DELETED);
        $this->lock->release($lock_id);
        if ($recursive_export) {
          // Re-throw the same exception so that it could be caught by parent
          // entity sync process.
          throw $e;
        }
        continue;
      }

      try {
        // Export.
        // NOTE: This try...catch block is NOT merged with one above since
        // both assertShouldSync() and export() may throw SyncExcludedException.
        $odoo_id = $sync_plugin
          ->assertEntity($entity)
          ->export($entity);
      }
      catch (Exception $e) {
        // Catch any exceptions thrown by dependant plugins and wrap them into
        // own exception to provide entity information.
        // Set sync status to 'failed' and release the lock.
        $this->idMap->setSyncStatus($entity_type, $odoo_model, $export_type, [$entity_id => NULL], MappingManagerInterface::STATUS_FAILED);
        $this->lock->release($lock_id);

        throw new RecursiveExportException($entity_type, $odoo_model, $export_type, $entity_id, $e);
      }

      // Set sync status to 'synced'.
      $this->idMap->setSyncStatus($entity_type, $odoo_model, $export_type, [$entity_id => $odoo_id], MappingManagerInterface::STATUS_SYNCED);

      // Release the lock.
      $this->lock->release($lock_id);
    }

    if ($missing = array_diff($entity_ids, $found)) {
      $this->idMap->setSyncStatus($entity_type, $odoo_model, $export_type, array_fill_keys($missing, NULL), MappingManagerInterface::STATUS_ENTITY_LOAD_ERROR);
    }

    return $this->idMap->getIdMap($entity_type, $odoo_model, $export_type, $entity_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function delayedSync($entity_type, $odoo_model, $export_type, $entity_ids) {
    // Make sure there's a plugin, fail early otherwise.
    $this->pluginManager->getInstanceByType($entity_type, $odoo_model, $export_type);

    if (!is_array($entity_ids)) {
      $entity_ids = [$entity_ids];
    }
    foreach ($entity_ids as $id) {
      $this->delayedSyncIds[$entity_type][$odoo_model][$export_type][$id] = $id;
    }

    // Update database, set sync status to NOT_SYNCED.
    $this
      ->idMap
      ->setSyncStatus($entity_type, $odoo_model, $export_type, array_fill_keys($entity_ids, NULL), MappingManagerInterface::STATUS_NOT_SYNCED);

    // Register shutdown function.
    if (!$this->shutdownFunctionRegistered) {
      drupal_register_shutdown_function([$this, 'syncAndFlush']);
      $this->shutdownFunctionRegistered = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function unsetDelayedSync($entity_type, $odoo_model, $export_type, $entity_ids) {
    if (!is_array($entity_ids)) {
      $entity_ids = [$entity_ids];
    }
    foreach ($entity_ids as $id) {
      if (isset($this->delayedSyncIds[$entity_type][$odoo_model][$export_type][$id])) {
        $this->delayedSyncIds[$entity_type][$odoo_model][$export_type][$id] = FALSE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function syncAndFlush() {
    $this->syncPending($this->delayedSyncIds);
    $this->delayedSyncIds = [];
  }

  /**
   * {@inheritdoc}
   */
  public function flush() {
    $this->delayedSyncIds = [];
  }

  /**
   * {@inheritdoc}
   */
  public function syncOnCron() {
    Timer::start(static::CRON_TIMER);
    // @TODO: Configurable Cron sync limit.
    // 10000 ms == 10s
    while (Timer::read(static::CRON_TIMER) <= 20000
      && $queue = $this->idMap->getSyncQueue(100, TRUE)) {
      $this->syncPending($queue);
    }
    Timer::stop(static::CRON_TIMER);
  }

  /**
   * Find and force export missing entities.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $odoo_model
   *   Odoo model name.
   * @param string $export_type
   *   Export type.
   * @param array $entity_ids
   *   Array of IDs of entities.
   * @param bool $force_update_changed
   *   Set TRUE to make sure exported entity is up to date. By default, this
   *   method may skip re-export if the entity has changed since last sync.
   *
   * @throws \Drupal\odoo_api_entity_sync\Exception\ExportException
   *   Export exception.
   */
  protected function exportMissing($entity_type, $odoo_model, $export_type, array $entity_ids, $force_update_changed = FALSE) {
    $missing_ids = array_filter($this->idMap->getSyncStatus($entity_type, $odoo_model, $export_type, $entity_ids), function ($row) use ($force_update_changed) {
      // FALSE means entity was never exported.
      // 0 means entity export was executed but never completed.
      // If the entity is deleted on Odoo - reexport it again.
      return $row === FALSE
        || $row['odoo_id'] === 0
        || $row['odoo_id'] === NULL
        || $row['status'] == MappingManagerInterface::STATUS_FAILED
        || $row['status'] == MappingManagerInterface::STATUS_DELETED
        || ($row['status'] == MappingManagerInterface::STATUS_NOT_SYNCED && $force_update_changed);
    });
    $missing_ids = array_keys($missing_ids);
    if ($missing_ids) {
      $this->sync($entity_type, $odoo_model, $export_type, $missing_ids, TRUE);

      // Avoid syncing same entity twice.
      $this->unsetDelayedSync($entity_type, $odoo_model, $export_type, $missing_ids);
    }
  }

  /**
   * Get sync lock ID.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $odoo_model
   *   Odoo model name.
   * @param string $export_type
   *   Export type.
   * @param int $entity_id
   *   Entity ID.
   *
   * @return string
   *   Lock ID.
   */
  protected function getLockId($entity_type, $odoo_model, $export_type, $entity_id) {
    return implode(':', [$entity_type, $odoo_model, $export_type, $entity_id]);
  }

  /**
   * Sync entities queued.
   *
   * @param array $queue
   *   Multi-dimensional array of items to sync.
   *   Entity type => Odoo model => Export type => Entity ID =>
   *   Entity ID/status.
   */
  protected function syncPending(array &$queue) {
    foreach ($queue as $entity_type => $odoo_models) {
      foreach ($odoo_models as $odoo_model => $export_types) {
        foreach ($export_types as $export_type => $entity_ids) {
          // Using loadMultiple to warp up static entity storage cache.
          try {
            $this
              ->entityTypeManager
              ->getStorage($entity_type)
              ->loadMultiple($entity_ids);
          }
          catch (InvalidPluginDefinitionException $e) {
            $this->log->error('Export exception while loading entities, message: ' . $e->getMessage());
            continue;
          }
          foreach ($entity_ids as $entity_id => $status) {
            if ($status === FALSE) {
              // Avoid syncing same entities twice. First, the entity may be
              // exported as a dependency, and second time it could be processed
              // individually.
              continue;
            }

            try {
              $this->sync($entity_type, $odoo_model, $export_type, $entity_id);
            }
            catch (ExportException $e) {
              $this->log->error('Export exception, message: ' . $e->getLogMessage());
              $this->eventDispatcher->dispatch(EntitySyncError::SYNC_ERROR, new EntitySyncError($e));
            }
          }
        }
      }
    }
  }

}
