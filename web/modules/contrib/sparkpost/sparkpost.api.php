<?php

/**
 * @file
 * Hooks provided by the Sparkpost module.
 */

/**
 * Alter the sparkpost message before it gets sent to sparkpost.
 *
 * @param array $sparkpost_message
 *   The sparkpost message, about to be sent to the webservice.
 * @param array $message
 *   The Drupal message. That is, the array that is passed to the mail system.
 */
function hook_sparkpost_mail_alter(array &$sparkpost_message, array &$message) {
  // If the message is a specific type of mail, we want to alter the reply-to.
  if ($message['id'] == 'mymodule_some_key') {
    $sparkpost_message['content']['reply_to'] = 'noreply@example.com';
  }
}
