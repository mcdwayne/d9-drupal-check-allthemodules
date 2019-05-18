<?php

namespace Drupal\audit_log\EventSubscriber;

use Drupal\audit_log\AuditLogEventInterface;

/**
 * Defines an event subscriber for responding to events.
 *
 * @package Drupal\audit_log\EventSubscriber
 */
interface EventSubscriberInterface {

  /**
   * Processes an event.
   *
   * @param \Drupal\audit_log\AuditLogEventInterface $event
   *   The audit event.
   *
   * @return bool
   *   TRUE if the event subscriber handled the event.
   */
  public function reactTo(AuditLogEventInterface $event);

  /**
   * Return entity type machine name.
   */
  public function getEntityType();

}
