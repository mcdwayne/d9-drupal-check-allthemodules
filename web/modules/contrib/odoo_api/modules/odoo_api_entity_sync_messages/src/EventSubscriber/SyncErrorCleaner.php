<?php

namespace Drupal\odoo_api_entity_sync_messages\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\odoo_api_entity_sync\Event\SyncStatusSaveEvent;
use Drupal\odoo_api_entity_sync\MappingManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SyncErrorCleaner.
 *
 * Subscribes for the Odoo export events and cleans up previous error messages.
 */
class SyncErrorCleaner implements EventSubscriberInterface {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * SyncErrorLogger constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SyncStatusSaveEvent::STATUS_SAVE] = ['cleanUpSyncErrors'];
    return $events;
  }

  /**
   * Removes all error logs based on entity sync status.
   *
   * @param \Drupal\odoo_api_entity_sync\Event\SyncStatusSaveEvent $event
   *   The sync status save event.
   */
  public function cleanUpSyncErrors(SyncStatusSaveEvent $event) {
    $remove_messages_on_statuses = [
      MappingManagerInterface::STATUS_SYNC_EXCLUDED,
      MappingManagerInterface::STATUS_DELETED,
      MappingManagerInterface::STATUS_SYNCED,
    ];
    if (in_array($event->getStatus(), $remove_messages_on_statuses)) {
      $this
        ->connection
        ->delete('odoo_api_entity_sync_messages')
        ->condition('entity_type', $event->getEntityType(), '=')
        ->condition('entity_id', array_keys($event->getIdMap()), 'IN')
        ->condition('odoo_model', $event->getOdooModel(), '=')
        ->condition('export_type', $event->getExportType(), '=')
        ->execute();
    }
  }

}
