<?php

namespace Drupal\entity_pilot;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Interface for a flight (arrival or departure).
 */
interface FlightInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Flight has been created, but dependencies are still to be reviewed.
   */
  const STATUS_PENDING = 1;

  /**
   * Flight is booked for departure/arrival.
   */
  const STATUS_READY = 2;

  /**
   * Flight is ready to be sent/received, queued awaiting send/receive.
   */
  const STATUS_QUEUED = 3;

  /**
   * Flight has been sent/accepted.
   */
  const STATUS_LANDED = 4;

  /**
   * Flight has been archived.
   */
  const STATUS_ARCHIVED = 5;

  /**
   * Returns the flight status.
   *
   * @return int
   *   One of the \Drupal\entity_pilot\FlightInterface state constants
   */
  public function getStatus();

  /**
   * Sets the flight status.
   *
   * @param int $status
   *   The new status.
   *
   * @return \Drupal\entity_pilot\FlightInterface
   *   The instance on which the method was called.
   */
  public function setStatus($status);

  /**
   * Checks if the flight status is pending.
   *
   * @return bool
   *   TRUE if the flight status is pending.
   */
  public function isPending();

  /**
   * Checks if the flight status is ready.
   *
   * @return bool
   *   TRUE if the flight status is ready.
   */
  public function isReady();

  /**
   * Checks if the flight status is queued.
   *
   * @return bool
   *   TRUE if the flight status is queued.
   */
  public function isQueued();

  /**
   * Checks if the flight status is landed (sent/received).
   *
   * @return bool
   *   TRUE if the flight status is landed (sent/received).
   */
  public function isLanded();

  /**
   * Checks if the flight status is archived.
   *
   * @return bool
   *   TRUE if the flight status is archived.
   */
  public function isArchived();

  /**
   * Returns the remote status.
   *
   * @return int
   *   The remote ID of the flight if set.
   */
  public function getRemoteId();

  /**
   * Sets the remote ID of the flight.
   *
   * @param int $remote_id
   *   The new remote ID.
   *
   * @return self
   *   The instance on which the method was called.
   */
  public function setRemoteId($remote_id);

  /**
   * Returns the flight revision log message.
   *
   * @return string
   *   The revision log message.
   */
  public function getRevisionLog();

  /**
   * Sets the flight revision log message.
   *
   * @param string $revision_log
   *   The revision log message.
   *
   * @return \Drupal\entity_pilot\FlightInterface
   *   The class instance that this method is called on.
   */
  public function setRevisionLog($revision_log);

  /**
   * Sets the flight description.
   *
   * @param string $info
   *   Flight information.
   *
   * @return \Drupal\entity_pilot\FlightInterface
   *   The class instance that this method is called on.
   */
  public function setInfo($info);

  /**
   * Gets the flight description.
   *
   * @return string
   *   The flight description.
   */
  public function getInfo();

  /**
   * Enforces an entity to be saved as a new revision.
   *
   * @param bool $value
   *   (optional) Whether a new revision should be saved.
   *
   * @throws \LogicException
   *   Thrown if the entity does not support revisions.
   *
   * @return \Drupal\entity_pilot\FlightInterface
   *   The instance the method was called on.
   */
  public function setNewRevision($value = TRUE);

  /**
   * Returns the account for the flight.
   *
   * @return \Drupal\entity_pilot\AccountInterface
   *   The account for this flight.
   */
  public function getAccount();

}
