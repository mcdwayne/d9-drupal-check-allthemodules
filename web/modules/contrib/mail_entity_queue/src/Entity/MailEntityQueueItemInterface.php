<?php

namespace Drupal\mail_entity_queue\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Defines the interface for mail entity queue item.
 */
interface MailEntityQueueItemInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Mail queue item status value for pending item.
   */
  const PENDING = 0;

  /**
   * Mail queue item status value for sent item.
   */
  const SENT = 1;

  /**
   * Mail queue item status value for retrying item.
   */
  const RETRYING = 2;

  /**
   * Mail queue item status value for discarded item.
   */
  const DISCARDED = 3;


  /**
   * Returns all possible statuses for a queue item.
   *
   * @return array
   *  Array of possible status for a Queue Item.
   */
  public static function getStatusOptions();

  /**
   * Gets the number of times that this item has been tried to
   * process.
   *
   * @return integer
   *   The number of times that this item has been tried to
   *   process.
   */
  public function getAttempts();

  /**
   * Gets the number of times that this item has been tried to
   * process.
   *
   * @param integer $attempts
   *   The number of times that this item has been tried to
   *   process.
   *
   * @return $this
   */
  public function setAttempts($attempts);

  /**
   * Gets the item creation timestamp.
   *
   * @return int
   *   Creation timestamp of the item.
   */
  public function getCreatedTime();

  /**
   * Sets the item creation timestamp.
   *
   * @param int $timestamp
   *   The item creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the item operation data.
   *
   * @return array
   *   The serialized array with the operation data.
   */
  public function getData();

  /**
   * Sets the item operation data.
   *
   * @param array $data
   *   The serialized array with the operation data.
   *
   * @return $this
   */
  public function setData($data);

  /**
   * Gets the address the message will be sent to.
   *
   * @return string
   */
  public function getMail();

  /**
   * Sets the address the message will be sent to.
   *
   * @param string $mail
   *   The address the message will be sent to.
   *
   * @return $this
   */
  public function setMail($mail);

  /**
   * Gets the entity ID that create this operation.
   *
   * @return int
   *   The entity ID that produces this operation.
   */
  public function getSourceEntityId();

  /**
   * Sets the entity ID that create this queue item.
   *
   * @param int $entity_id
   *   The entity ID that create this queue item.
   *
   * @return $this
   */
  public function setSourceEntityId($entity_id);

  /**
   * Gets the entity type that create this queue item.
   *
   * @return string
   *   The entity type that create this queue item.
   */
  public function getSourceEntityType();

  /**
   * Sets the entity type that produces this queue item.
   *
   * @param string $entity_type
   *   The entity type that create this queue item.
   *
   * @return $this
   */
  public function setSourceEntityType($entity_type);

  /**
   * Gets the mail queue instance this items pertains to.
   *
   * @return \Drupal\mail_entity_queue\Entity\MailEntityQueueInterface
   *   The mail queue instance.
   */
  public function queue();

  /**
   * Gets the item processing status code.
   *
   * @return integer
   */
  public function getStatus();

  /**
   * Sets the item processing status code.
   *
   * @param integer $code
   *   The item processing status code.
   *
   * @return $this
   */
  public function setStatus($code);
}
