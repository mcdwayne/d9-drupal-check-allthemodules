<?php

namespace Drupal\advanced_update;

use Drupal\advanced_update\Entity\AdvancedUpdateEntity;

/**
 * Class AdvancedUpdateStatus.
 *
 * This class is used to store update status and entities in a same object.
 */
class AdvancedUpdateStatus {
  /**
   * Indicate an update not treated yet.
   */
  const STATUS_NONE = 'none';
  /**
   * Indicate an update passed successfully.
   */
  const STATUS_DONE = 'done';
  /**
   * Indicate an update failed.
   */
  const STATUS_FAILED = 'failed';
  /**
   * An object AdvancedUpdateEntity.
   *
   * @var $updateEntity
   */
  protected $updateEntity;
  /**
   * A string status.
   *
   * @var $status
   */
  protected $status;
  /**
   * A string message.
   *
   * @var $message
   */
  protected $message;

  /**
   * AdvancedUpdateStatus constructor.
   *
   * @param AdvancedUpdateEntity $update_entity
   *    An entity object.
   */
  public function __construct(AdvancedUpdateEntity $update_entity) {
    $this->setUpdateEntity($update_entity);
    $this->setStatus(self::STATUS_NONE);
  }

  /**
   * Set an advanced update entity.
   *
   * @param AdvancedUpdateEntity $update_entity
   *    An advanced update entity to perform.
   */
  protected function setUpdateEntity(AdvancedUpdateEntity $update_entity) {
    $this->updateEntity = $update_entity;
  }

  /**
   * Set a status for an advanced update entity.
   *
   * @param string $status
   *    A string status.
   */
  public function setStatus($status) {
    if ($status === self::STATUS_NONE || $status === self::STATUS_FAILED || $status === self::STATUS_DONE) {
      $this->status = $status;
    }
  }

  /**
   * Set a string message for an advanced update entity.
   *
   * @param string $message
   *    An error message or an information message after trying update.
   */
  public function setMessage($message) {
    if (is_string($message)) {
      $this->message = $message;
    }
  }

  /**
   * Get the AdvancedUpdateEntity stored.
   *
   * @return AdvancedUpdateEntity
   *    The entity.
   */
  public function getUpdateEntity() {
    return $this->updateEntity;
  }

  /**
   * Get the status stored.
   *
   * @return string
   *    The status.
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Get the message stored.
   *
   * @return string
   *    The message.
   */
  public function getMessage() {
    return $this->message;
  }

}
