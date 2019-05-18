<?php

namespace Drupal\odoo_api_entity_sync\Event;

use Drupal\odoo_api_entity_sync\Exception\ExportException;
use Symfony\Component\EventDispatcher\Event;

/**
 * Entity sync error event.
 */
class EntitySyncError extends Event {

  const SYNC_ERROR = 'odoo_api_entity_sync.sync_error';

  /**
   * Exception thrown.
   *
   * @var \Drupal\odoo_api_entity_sync\Exception\ExportException
   */
  protected $exception;

  /**
   * EntitySyncError constructor.
   *
   * @param \Drupal\odoo_api_entity_sync\Exception\ExportException $exception
   *   The export exception.
   */
  public function __construct(ExportException $exception) {
    $this->exception = $exception;
  }

  /**
   * Export exception getter.
   *
   * @return \Drupal\odoo_api_entity_sync\Exception\ExportException
   *   The export exception thrown on entity sync.
   */
  public function getException() {
    return $this->exception;
  }

}
