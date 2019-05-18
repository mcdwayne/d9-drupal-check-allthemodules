<?php

namespace Drupal\audit_log;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Represents a single auditable event for logging.
 *
 * @package Drupal\audit_log
 */
interface AuditLogEventInterface {

  /**
   * Stores the user that triggers the audit event.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user object of the user performing an action to be logged.
   *
   * @return AuditLogEventInterface
   *   The current instance of the object.
   */
  public function setUser(AccountInterface $user);

  /**
   * Stores the entity that triggered the audit event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being modified.
   *
   * @return AuditLogEventInterface
   *   The current instance of the object.
   */
  public function setEntity(EntityInterface $entity);

  /**
   * Stores the untranslated audit message to write to the log.
   *
   * @param string $message
   *   The untranslated audit message.
   *
   * @return AuditLogEventInterface
   *   The current instance of the object.
   */
  public function setMessage($message);

  /**
   * Stores the replacement tokens for the log message.
   *
   * @param array $variables
   *   The array of replacement tokens.
   *
   * @return AuditLogEventInterface
   *   The current instance of the event.
   */
  public function setMessagePlaceholders(array $variables);

  /**
   * Stores the type of event being reported.
   *
   * @param string $event_type
   *   The type of event being reported.
   *   Example: "insert", "update", "delete".
   *
   * @return AuditLogEventInterface
   *   The current instance of the object.
   */
  public function setEventType($event_type);

  /**
   * Stores the original state of the object before the event occurred.
   *
   * @param string $state
   *   The name of the object state such as "published" or "active".
   *
   * @return AuditLogEventInterface
   *   The current instance of the object.
   */
  public function setPreviousState($state);

  /**
   * Stores the new state of the object after the event occurred.
   *
   * @param string $state
   *   The name of the object state such as "published" or "active".
   *
   * @return AuditLogEventInterface
   *   The current instance of the object.
   */
  public function setCurrentState($state);

  /**
   * Sets the timestamp for the event request.
   *
   * @param int $request_time
   *   The timestamp of the request.
   *
   * @return \Drupal\audit_log\AuditLogEventInterface
   *   The current instance of the object.
   */
  public function setRequestTime($request_time);

  /**
   * Retrieves the user object for the user that triggered the event.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The user object for the user that triggered the event.
   */
  public function getUser();

  /**
   * Retrieves the entity that was modified.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity that was modified.
   */
  public function getEntity();

  /**
   * Retrieves the untranslated audit log message for the event.
   *
   * @return string
   *   The untranslated audit log message.
   */
  public function getMessage();

  /**
   * Retrieves the replacement tokens for the log message.
   *
   * @return array
   *   The replacement tokens for the log message.
   */
  public function getMessagePlaceholders();

  /**
   * Retrieves the type of event that was triggered.
   *
   * @return string
   *   The type of event such as "insert", "update", "delete".
   */
  public function getEventType();

  /**
   * Retrieves the original state of the object before the event occurred.
   *
   * @return string
   *   The name of the object state such as "published" or "active".
   */
  public function getPreviousState();

  /**
   * Retrieves the new state of the object after the event occurred.
   *
   * @return string
   *   The name of the object state such as "published" or "active".
   */
  public function getCurrentState();

  /**
   * The timestamp for when the event was initiated.
   *
   * @return int
   *   Timestamp value.
   */
  public function getRequestTime();

}
