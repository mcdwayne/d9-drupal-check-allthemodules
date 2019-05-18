<?php

namespace Drupal\odoo_api_entity_sync;

/**
 * Interface MappingManagerInterface.
 */
interface MappingManagerInterface {

  // Export on cron flags.
  const CRON_EXPORT_ENABLED = 1;
  const CRON_EXPORT_DISABLED = 0;

  // Sync statuses.
  const STATUS_NOT_SYNCED = 0;
  const STATUS_IN_PROGRESS = 1;
  const STATUS_SYNCED = 2;
  const STATUS_FAILED = 3;
  const STATUS_SYNC_EXCLUDED = 4;
  const STATUS_DELETION_IN_PROGRESS = 5;
  const STATUS_DELETED = 6;
  const STATUS_ENTITY_LOAD_ERROR = 7;

  /**
   * Get sync status for given entities.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $odoo_model
   *   Odoo model name.
   * @param string $export_type
   *   Export type.
   * @param int|array $entity_id
   *   Entity ID or an array of IDs.
   *
   * @return array
   *   An array of results keyed by entity ID.
   *   Each result may be either FALSE if entity was not exported OR an array
   *   with the following:
   *     status: one MappingManagerInterface::STATUS_* constants,
   *     odoo_id: Odoo object ID,
   *     sync_time: last sync timestamp.
   */
  public function getSyncStatus($entity_type, $odoo_model, $export_type, $entity_id);

  /**
   * Set new sync status for given entities.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $odoo_model
   *   Odoo model name.
   * @param string $export_type
   *   Export type.
   * @param array $id_map
   *   An array of entity ID => Odoo ID.
   * @param int $status
   *   New sync status.
   * @param bool $cron_export
   *   TRUE / FALSE.
   *
   * @throws \InvalidArgumentException
   *   Incorrect status.
   */
  public function setSyncStatus($entity_type, $odoo_model, $export_type, array $id_map, $status, $cron_export = TRUE);

  /**
   * Get ID map for given entities.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $odoo_model
   *   Odoo model name.
   * @param string $export_type
   *   Export type.
   * @param int|array $entity_id
   *   Entity ID or an array of IDs.
   *
   * @return array
   *   An array of entity ID => Odoo object ID.
   */
  public function getIdMap($entity_type, $odoo_model, $export_type, $entity_id);

  /**
   * Count items pending sync.
   *
   * @param bool|null $cron_export
   *   Controls fetching items exported on cron.
   *   Possible values are:
   *
   *   - NULL: get all items (do not apply condition).
   *   - TRUE: get items which should be exported on cron.
   *   - FALSE: get all items excluded from cron export.
   *
   * @return int
   *   The number of items in the sync queue.
   */
  public function countSyncQueue($cron_export);

  /**
   * Get items pending sync.
   *
   * @param int $limit
   *   Fetch limit.
   * @param bool|null $cron_export
   *   Controls fetching items exported on cron.
   *   Possible values are:
   *
   *   - NULL: get all items (do not apply condition).
   *   - TRUE: get items which should be exported on cron.
   *   - FALSE: get all items excluded from cron export.
   *
   * @return array
   *   A multidimensional associative array with keys: entity_type, odoo_model,
   *   export_type, entity_id.
   *   The indexed array values of each contain the set messages for that type,
   *   and each message is an associative array with the following format:
   *   - entity_type => odoo_model => export_type => entity_id => entity_id.
   *
   *   So, the following is an example of the full return array structure:
   *
   * @code
   *     array(
   *       'user' => array(
   *         'res.partner' => array(
   *           'export_type_default' => array(
   *             'entity_id_1' => 'entity_id_1',
   *             'entity_id_2' => 'entity_id_2',
   *           ),
   *         ),
   *       ),
   *     );
   * @endcode
   */
  public function getSyncQueue($limit, $cron_export);

  /**
   * Find IDs of Drupal entities exported to Drupal.
   *
   * @param string $odoo_model
   *   Odoo model name.
   * @param array $odoo_ids
   *   Array of Odoo IDs.
   *
   * @return array
   *   A multidimensional associative array, keyed by Odoo model ID.
   *   Each array element may be either:
   *   - an array of entity_type => export_type => entity_id,
   *   - FALSE if there's not corresponding entity.
   */
  public function findMappedEntities($odoo_model, array $odoo_ids);

}
