<?php

namespace Drupal\odoo_api_entity_sync\Event;

/**
 * Wraps a odoo_api_entity_sync status save event for event listeners.
 */
class SyncStatusSaveEvent extends OdooEventBase {

  /**
   * Name of the event fired when setting a status an id map.
   *
   * This event allows modules to perform an action whenever the id map got
   * a sync status. The event listener method receives
   * a \Drupal\odoo_api_entity_sync\Event\SyncStatusSaveEvent instance.
   *
   * @Event
   *
   * @see \Drupal\migrate\Event\MigrateMapSaveEvent
   *
   * @var string
   */
  const STATUS_SAVE = 'odoo_api_entity_sync.status_save';

  /**
   * The entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * An array of entity ID => Odoo ID.
   *
   * @var array
   */
  protected $idMap;

  /**
   * The sync status.
   *
   * @var int
   * @see \Drupal\odoo_api_entity_sync\MappingManagerInterface
   */
  protected $status;

  /**
   * A flag indicating whether the record should be exported on cron.
   *
   * @var bool
   * @see \Drupal\odoo_api_entity_sync\MappingManagerInterface
   */
  protected $cronExport;

  /**
   * SyncStatusSaveEvent constructor.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $odoo_model
   *   Odoo object model name.
   * @param string $export_type
   *   Export type.
   * @param array $id_map
   *   The id map.
   * @param int $status
   *   The sync status.
   * @param bool $cron_export
   *   A flag indicating whether the record should be exported on cron.
   */
  public function __construct($entity_type, $odoo_model, $export_type, array $id_map, $status, $cron_export) {
    parent::__construct($odoo_model, $export_type);
    $this->entityType = $entity_type;
    $this->idMap = $id_map;
    $this->status = $status;
    $this->cronExport = $cron_export;
  }

  /**
   * Entity type getter.
   *
   * @return string
   *   The entity type.
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * Id map getter.
   *
   * @return array
   *   The id map.
   */
  public function getIdMap() {
    return $this->idMap;
  }

  /**
   * Sync status getter.
   *
   * @return int
   *   The sync status.
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Cron export getter.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  public function getCronExport() {
    return $this->cronExport;
  }

}
