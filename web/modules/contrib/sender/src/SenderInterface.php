<?php

namespace Drupal\sender;

/**
 * Sender service interface.
 */
interface SenderInterface {

  /**
   * Sends a message to specified recipients.
   *
   * @param string|\Drupal\sender\Entity\Message $message
   *   The message ID or the loaded message object to be sent.
   * @param array|\Drupal\Core\Session\AccountInterface $recipients
   *   The user account of the recipient or an array of user accounts of
   *   recipients. If empty, the current user is used.
   * @param array $data
   *   Data to be used for token replacements when building the message.
   * @param string|array $method_ids
   *   The name of the plugin that will be used to actually send the message
   *   (e.g. "sender_email") or an array of plugin names. By default, sends
   *   through all available methods.
   *
   * @see \Drupal\Core\Utility\Token::replace()
   */
  public function send($message, $recipients = NULL, array $data = [], $method_ids = []);

}
