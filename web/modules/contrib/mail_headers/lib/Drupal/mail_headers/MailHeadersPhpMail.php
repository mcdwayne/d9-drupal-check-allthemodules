<?php
/**
 * @file
 * Definition of Drupal\mail_headers\MailHeadersPhpMail.
 */

namespace Drupal\mail_headers;

use Drupal\Core\Mail\PhpMail;

/**
 * Modify the drupal mail system to allow HTML.
 */
class MailHeadersPhpMail extends PhpMail {

  /**
   * Concatenate and wrap the e-mail body for either
   * plain-text or HTML emails.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return array
   *   The formatted $message.
   */
  public function format(array $message) {
    if (!config('mail_headers.settings')->get('html')) {
      parent::format($message);
    }
    else {
      // Join the body array into one string.
      $message['body'] = implode("\n\n", $message['body']);
    }

    return $message;
  }
}
