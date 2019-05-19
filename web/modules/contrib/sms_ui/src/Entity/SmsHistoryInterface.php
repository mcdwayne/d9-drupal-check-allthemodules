<?php

namespace Drupal\sms_ui\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Entity\SmsMessageInterface as EntitySmsMessageInterface;

/**
 * Provides an interface for SMS history.
 *
 * Sms History is made of a combination of SMS messages that were sent in the
 * same batch.
 */
interface SmsHistoryInterface extends ContentEntityInterface {

  /**
   * Sets the status of the message history.
   *
   * @param string $status
   *   The status of the message history: 'draft' or 'sent'.
   *
   * @return $this
   */
  public function setStatus($status);

  /**
   * Gets the status of the message history.
   *
   * @return string
   */
  public function getStatus();

  /**
   * Sets the time when this history item should expire.
   *
   * @param int $value
   *   The UNIX timestamp when this item should expire. It will be deleted then.
   *
   * @return $this
   */
  public function setExpiry($value);

  /**
   * Gets when this history item will expire.
   *
   * @return int
   *   A UNIX timestamp.
   */
  public function getExpiry();

  /**
   * Sets all the SMS message entities in this history item.
   *
   * @param \Drupal\sms\Entity\SmsMessageInterface[]
   *   The SMS messages to be in this history item.
   *
   * @return $this
   */
  public function setSmsMessages(array $sms_messages);

  /**
   * Gets all the SMS message entities in this history item.
   *
   * @return \Drupal\sms\Entity\SmsMessageInterface[]
   */
  public function getSmsMessages();

  /**
   * Adds an SMS message entity to this history item.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms_message
   *   The message to be added
   *
   * @return $this
   */
  public function addSmsMessage(SmsMessageInterface $sms_message);

  /**
   * Deletes all the SMS message entities in this history item.
   *
   * @return $this
   */
  public function deleteSmsMessages();

  /**
   * Gets the actual message sent.
   *
   * @return string|null
   *   The SMS message body.
   */
  public function getMessage();

  /**
   * Gets all the SMS message results in this history item.
   *
   * @return \Drupal\sms\Entity\SmsMessageResultInterface[]
   */
  public function getResults();

  /**
   * Gets all the SMS delivery reports in this history item.
   *
   * @return \Drupal\sms\Entity\SmsDeliveryReportInterface[]
   */
  public function getReports();

  /**
   * Gets all the recipient numbers.
   *
   * @return string[]
   */
  public function getRecipients();

  /**
   * Gets the sender of the SMS message.
   *
   * @return string|null
   */
  public function getSender();

  /**
   * Gets the user under whose account the messages were sent.
   *
   * @return \Drupal\user\UserInterface|null
   */
  public function getOwner();

  /**
   * Gets the SMS history entity that an SMS message belongs to.
   *
   * @param \Drupal\sms\Entity\SmsMessageInterface $sms_message
   *   The SMS message.
   *
   * @return static|null
   *   An SMS history object or null if there is no matching one.
   */
  public static function getHistoryForMessage(EntitySmsMessageInterface $sms_message);

}
