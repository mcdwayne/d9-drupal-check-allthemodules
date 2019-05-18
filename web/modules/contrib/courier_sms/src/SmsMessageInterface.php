<?php

namespace Drupal\courier_sms;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines an interface for the SMS message entity.
 */
interface SmsMessageInterface extends ContentEntityInterface {

  /**
   * Gets the recipient SMS message.
   *
   * @return string
   *   Get the recipient for the SMS message.
   */
  public function getRecipient();

  /**
   * Set recipient for the SMS message.
   *
   * @param string $recipient
   *   The recipient to add.
   *
   * @return static
   *   The called SMS message object.
   */
  public function setRecipient($recipient);

  /**
   * Gets the message to be sent.
   *
   * @return string
   *   Get the message to be sent.
   */
  public function getMessage();

  /**
   * Set the message to be sent.
   *
   * @param string $message
   *   The message to be sent.
   *
   * @return static
   *   The called SMS message object.
   */
  public function setMessage($message);

}
