<?php

namespace Drupal\phpmail_alter\Hook;

/**
 * PhpMail alter hook_mail_alter().
 */
class MailAlter {

  /**
   * Hook init.
   */
  public static function hook(&$message) {
    $config = \Drupal::config('phpmail_alter.settings');
    // From Header.
    if ($config->get('from')) {
      $message['headers']['From'] = $config->get('from');
    }
    if ($config->get('reply')) {
      $message['headers']['Reply-to'] = $config->get('reply');
    }
    // Rewrite phpmail.
    if ($config->get('phpmail')) {
      $message['send'] = FALSE;
      \Drupal::service('phpmail_alter')->mail($message);
    }
  }

}
