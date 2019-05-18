<?php

namespace Drupal\mail_entity_queue\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Defines the interface for mail entity queue.
 */
interface MailEntityQueueInterface extends ConfigEntityInterface, EntityDescriptionInterface {

  /**
   * Gets the number of items to process in each cron run.
   *
   * @return integer
   */
  public function getCronItems();

  /**
   * Sets the number of items to process in each cron run.
   *
   * @param integer $items
   *
   * @return $this
   */
  public function setCronItems(integer $items);

  /**
   * Gets the pause between execution of queue elements.
   *
   * @return integer
   *   The mail entity queue delay in milliseconds for this queue.
   */
  public function getCronDelay();

  /**
   * Sets the pause between execution of queue elements.
   *
   * @param integer $delay
   *   The delay in milliseconds.
   *
   * @return $this
   */
  public function setCronDelay(integer $delay);

  /**
   * Gets an instanced of the mail entity queue processor plugin.
   *
   * @return \Drupal\mail_entity_queue\Plugin\MailEntityQueueProcessorInterface
   */
  public function getQueueProcessor();

  /**
   * Gets the mail entity queue plugin processor id.
   *
   * @return string
   */
  public function getQueueProcessorId();

  /**
   * Gets the e-mail format.
   *
   * @return string
   */
  public function getFormat();

  /**
   * Sets the format for the e-mail.
   *
   * @param string $format
   *   The format for the e-mail.
   *
   * @return $this
   */
  public function setFormat(string $format);

  /**
   * Sets the mail entity queue plugin processor id.
   *
   * @param string $queue_processor
   *   The mail entity queue plugin processor id.
   *
   * @return $this
   */
  public function setQueueProcessorId(string $queue_processor);

  /**
   * Adds an item to the queue.
   *
   * @param string $mail
   *   The address the message will be sent to.
   * @param array $data
   *   Data array to compose the mail.
   * @param string $entity_type
   *   Type of the entity related to the item.
   * @param string $entity_id
   *   Id of the entity related to the item.
   *
   * @return \Drupal\mail_entity_queue\Entity\MailEntityQueueItemInterface
   *   The mail entity queue item added.
   */
  public function addItem(string $mail, array $data, $entity_type = NULL, $entity_id = NULL);

}
