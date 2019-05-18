<?php

namespace Drupal\odoo_api_entity_sync;

/**
 * Interface SyncManagerInterface.
 */
interface SyncManagerInterface {

  /**
   * Ensure given entity is exported.
   *
   * This method will *not* sync object immediately if it's already exported.
   * Instead, it will return a list of IDs.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $odoo_model
   *   Odoo model name.
   * @param string $export_type
   *   Export type.
   * @param int|array $entity_id
   *   Entity ID or an array of IDs.
   * @param bool $force_update_changed
   *   Set TRUE to make sure exported entity is up to date. By default, this
   *   method may skip re-export if the entity has changed since last sync.
   *
   * @return array
   *   An array of entity ID => Odoo object ID.
   *
   * @throws \Drupal\odoo_api_entity_sync\Plugin\Exception\MissingPluginException
   *   Missing sync plugin.
   * @throws \Drupal\odoo_api_entity_sync\Exception\ExportException
   *   Export failure.
   */
  public function export($entity_type, $odoo_model, $export_type, $entity_id, $force_update_changed = FALSE);

  /**
   * Registers entities in the sync table with the NOT_SYNCED status.
   *
   * Registered entities won't be exported on cron.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $odoo_model
   *   Odoo model name.
   * @param string $export_type
   *   Export type.
   * @param int|array $entity_ids
   *   Entity ID or an array of IDs.
   */
  public function registerEntity($entity_type, $odoo_model, $export_type, $entity_ids);

  /**
   * Ensure given entity is synced.
   *
   * Unlike export(), this method will force re-export object
   * *immediately* if it's not in sync.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $odoo_model
   *   Odoo model name.
   * @param string $export_type
   *   Export type.
   * @param int|array $entity_ids
   *   Entity ID or an array of IDs.
   * @param bool $recursive_export
   *   Whether given entity is a dependency for something.
   *   Setting this to TRUE will enable throwing sync exclusion exceptions
   *   which may be then caught by the parent exporter process.
   *
   * @return array
   *   An array of entity ID => Odoo object ID.
   *
   * @throws \Drupal\odoo_api_entity_sync\Plugin\Exception\MissingPluginException
   *   Missing sync plugin.
   * @throws \Drupal\odoo_api_entity_sync\Exception\SyncExcludedException
   *   Entity is excluded from sync.
   * @throws \Drupal\odoo_api_entity_sync\Exception\ExportException
   *   Export failure.
   */
  public function sync($entity_type, $odoo_model, $export_type, $entity_ids, $recursive_export = FALSE);

  /**
   * Enqueue syncing the entity.
   *
   * The real sync may occur either at Drupal shutdown or Cron.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $odoo_model
   *   Odoo model name.
   * @param string $export_type
   *   Export type.
   * @param int|array $entity_ids
   *   Entity ID or an array of IDs.
   */
  public function delayedSync($entity_type, $odoo_model, $export_type, $entity_ids);

  /**
   * Remove the item from shutdown sync queue.
   *
   * This function is an opposite to delayedSync() and may be called by
   * export() to remove items from the queue.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $odoo_model
   *   Odoo model name.
   * @param string $export_type
   *   Export type.
   * @param int|array $entity_ids
   *   Entity ID or an array of IDs.
   *
   * @see \Drupal\odoo_api_entity_sync\SyncManagerInterface::delayedSync()
   */
  public function unsetDelayedSync($entity_type, $odoo_model, $export_type, $entity_ids);

  /**
   * Run all sync actions enqueued by delayedSync() and flush the queue.
   *
   * @see \Drupal\odoo_api_entity_sync\SyncManagerInterface::delayedSync()
   */
  public function syncAndFlush();

  /**
   * Flushes the shutdown sync queue.
   *
   * @see \Drupal\odoo_api_entity_sync\SyncManagerInterface::delayedSync()
   */
  public function flush();

  /**
   * Run all pending sync actions on Cron.
   *
   * @see \Drupal\odoo_api_entity_sync\SyncManagerInterface::delayedSync()
   */
  public function syncOnCron();

}
