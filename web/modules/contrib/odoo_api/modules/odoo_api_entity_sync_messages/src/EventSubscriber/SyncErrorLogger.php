<?php

namespace Drupal\odoo_api_entity_sync_messages\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\odoo_api_entity_sync\Event\EntitySyncError;
use Drupal\odoo_api_entity_sync\Exception\ExportException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class SyncErrorLogger.
 *
 * Subscribes for the Odoo sync error event and logs messages to the database.
 */
class SyncErrorLogger implements EventSubscriberInterface {

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
    $events[EntitySyncError::SYNC_ERROR] = ['handleSyncError'];

    return $events;
  }

  /**
   * Reacts on sync error.
   *
   * @param \Drupal\odoo_api_entity_sync\Event\EntitySyncError $event
   *   The event object.
   *
   * @throws \Exception
   *   An exception can be throw during executing a request to the database.
   */
  public function handleSyncError(EntitySyncError $event) {
    $parent_exception = $event->getException();
    $cause_exception = $this->getCauseException($parent_exception);
    $fields = [
      'cause_entity_type' => $cause_exception->getEntityType(),
      'cause_odoo_model' => $cause_exception->getOdooModel(),
      'cause_export_type' => $cause_exception->getExportType(),
      'cause_entity_id' => $cause_exception->getEntityId(),
    ];

    if ($previous = $cause_exception->getPrevious()) {
      // If there is a previous exception - that's not an ExportException.
      // See getCauseException().
      $fields['message'] = $previous->getMessage();
      $fields['exception_class'] = get_class($previous);
    }
    else {
      $fields['message'] = $cause_exception->getLogMessage();
      $fields['exception_class'] = get_class($cause_exception);
    }

    $this
      ->connection
      ->merge('odoo_api_entity_sync_messages')
      ->keys([
        'entity_type' => $parent_exception->getEntityType(),
        'odoo_model' => $parent_exception->getOdooModel(),
        'export_type' => $parent_exception->getExportType(),
        'entity_id' => $parent_exception->getEntityId(),
      ])
      ->fields($fields)
      ->execute();
  }

  /**
   * Gets the first export exception, which caused an error.
   *
   * @param \Drupal\odoo_api_entity_sync\Exception\ExportException $e
   *   The export exception.
   *
   * @return \Drupal\odoo_api_entity_sync\Exception\ExportException
   *   The initial export exception.
   */
  protected function getCauseException(ExportException $e) {
    if ($previous = $e->getPrevious()) {
      if ($previous instanceof ExportException) {
        return $this->getCauseException($previous);
      }
      else {
        return $e;
      }
    }

    return $e;
  }

}
