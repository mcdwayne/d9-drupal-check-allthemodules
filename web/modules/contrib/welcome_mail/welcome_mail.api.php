<?php

/**
 * @file
 * Welcome Mail API file.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Declaration of hook_welcome_mail_mail_alter().
 *
 * Let other modules alter the default message to be sent through welcome_mail
 * module. This will override its behavior via hook_welcome_mail_mail_alter().
 *
 * @param array $message
 *   An array with keys 'id', 'to', 'subject', 'body', 'from', 'headers', like
 *   in hook_mail(). Link to Drupal API follows:
 *   https://api.drupal.org/api/drupal/core%21core.api.php/function/hook_mail/.
 */
function hook_welcome_mail_mail_alter(array &$message) {
  return $message;
}

/**
 * @} End of "addtogroup hooks".
 */
