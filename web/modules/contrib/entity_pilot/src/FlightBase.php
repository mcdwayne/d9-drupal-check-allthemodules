<?php

namespace Drupal\entity_pilot;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines a base class for flight entities.
 */
abstract class FlightBase extends ContentEntityBase implements FlightInterface {

  use EntityChangedTrait;

  /**
   * Returns the departure status.
   *
   * @return int
   *   One of the \Drupal\entity_pilot\DepartureInterface state constants
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * Sets the flight status.
   *
   * @param int $status
   *   The new status.
   *
   * @throws \InvalidArgumentException
   *
   * @return self
   *   The instance on which the method was called.
   */
  public function setStatus($status) {
    if (!in_array($status, [
      FlightInterface::STATUS_PENDING,
      FlightInterface::STATUS_READY,
      FlightInterface::STATUS_QUEUED,
      FlightInterface::STATUS_LANDED,
      FlightInterface::STATUS_ARCHIVED,
    ])) {
      throw new \InvalidArgumentException(sprintf('%s is not a valid value for status', $status));
    }
    $this->set('status', $status);
    return $this;
  }

  /**
   * Checks if the flight status is pending.
   *
   * @return bool
   *   TRUE if the flight status is pending.
   */
  public function isPending() {
    return $this->getStatus() == FlightInterface::STATUS_PENDING;
  }

  /**
   * Checks if the flight status is ready.
   *
   * @return bool
   *   TRUE if the flight status is ready.
   */
  public function isReady() {
    return $this->getStatus() == FlightInterface::STATUS_READY;
  }

  /**
   * Checks if the flight status is queued.
   *
   * @return bool
   *   TRUE if the flight status is queued.
   */
  public function isQueued() {
    return $this->getStatus() == FlightInterface::STATUS_QUEUED;
  }

  /**
   * Checks if the flight status is landed (sent/received).
   *
   * @return bool
   *   TRUE if the flight status is landed (sent/received).
   */
  public function isLanded() {
    return $this->getStatus() == FlightInterface::STATUS_LANDED;
  }

  /**
   * Checks if the flight status is archived.
   *
   * @return bool
   *   TRUE if the flight status is archived.
   */
  public function isArchived() {
    return $this->getStatus() == FlightInterface::STATUS_ARCHIVED;
  }

  /**
   * Returns the remote status.
   *
   * @return int
   *   The remote ID of the flight if set.
   */
  public function getRemoteId() {
    return $this->get('remote_id')->value;
  }

  /**
   * Sets the remote ID of the flight.
   *
   * @param int $remote_id
   *   The new remote ID.
   *
   * @return self
   *   The instance on which the method was called.
   */
  public function setRemoteId($remote_id) {
    $this->set('remote_id', $remote_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setInfo($info) {
    $this->set('info', $info);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionLog() {
    return $this->get('log')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionLog($revision_log) {
    $this->set('log', $revision_log);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return $this->get('info')->value;
  }

  /**
   * Sets the revision state.
   *
   * @param bool $value
   *   Revision state.
   *
   * @return self
   *   Instance the method was called on.
   */
  public function setNewRevision($value = TRUE) {
    parent::setNewRevision($value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount() {
    return $this->get('account')->entity;
  }

}
