<?php

namespace Drupal\sender\Plugin\SenderMethod;

use Drupal\Core\Session\AccountInterface;
use Drupal\sender\Entity\MessageInterface;

/**
 * Interface for sender method plugins.
 */
interface SenderMethodInterface {

  /**
   * Sends a message.
   *
   * @param array $data
   *   An associative array containing following keys:
   *   * subject - The subject with tokens replaced.
   *   * body - An associtive array contining the body's format and text with
   *     tokens replaced.
   *   * rendered - A string representing the rendered message.
   * @param \Drupal\Core\Session\AccountInterface $recipient
   *   The account of the message's recipient.
   * @param \Drupal\sender\Entity\MessageInterface $message
   *   The message entity to be sent.
   */
  public function send(array $data, AccountInterface $recipient, MessageInterface $message);

  /**
   * Returns the ID of this plugin.
   *
   * @return string
   *   The method's ID.
   */
  public function id();

}
