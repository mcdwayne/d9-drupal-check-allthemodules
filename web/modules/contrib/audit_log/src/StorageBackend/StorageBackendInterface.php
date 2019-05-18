<?php

namespace Drupal\audit_log\StorageBackend;

use Drupal\audit_log\AuditLogEventInterface;

/**
 * Defines a logging storage backend to write audit events to.
 *
 * @package Drupal\audit_log\StorageBackend
 */
interface StorageBackendInterface {

  /**
   * Writes the event to the storage backend's storage system.
   *
   * @param \Drupal\audit_log\AuditLogEventInterface $event
   *   The event to be written to the log.
   */
  public function save(AuditLogEventInterface $event);

}
