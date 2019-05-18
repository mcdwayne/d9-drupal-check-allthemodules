<?php

/**
 * @file
 * Sender module hooks.
 */

/**
 * Allows modules to change the methods used to send a message.
 *
 * @param array $methods
 *   The list of sender methods names to be altered.
 * @param \Drupal\sender\Entity\MessageInterface $message
 *   The message being sent.
 */
function hook_sender_methods_alter(array &$methods, MessageInterface $message) {
  // @todo
}

/**
 * Allows modules to change the list of recipients before the message is sent.
 *
 * @param array $recipients
 *   The list of recipients (user accounts) to be altered.
 * @param \Drupal\sender\Entity\MessageInterface $message
 *   The message being sent.
 * @param array $methods
 *   The list of method IDs that will be used to send the message.
 */
function hook_sender_recipients_alter(array &$recipients, MessageInterface $message, array $methods) {
  // @todo
}

/**
 * Allows modules to change the data that will be passed to a method plugin.
 *
 * @param array $data
 *   The array to be altered, containing the following keys:
 *    * 'subject' - The message's subject with tokens replaced.
 *    * 'body' - The message's body with tokens replaced, including 'value' and
 *      'format' keys.
 *    * 'rendered' - The rendered message.
 * @param \Drupal\sender\Entity\Message $message
 *   The message being sent.
 * @param array $context
 *   An associative array containing the following keys:
 *    * 'recipient' - The recipient's user account
 *      (\Drupal\Core\Session\AccountInterface object).
 *    * 'method' - The sender method plugin
 *      (\Drupal\sender\SenderMethodInterface object).
 */
function hook_sender_message_data_alter(array &$data, Message $message, array $context) {
  // @todo
}
