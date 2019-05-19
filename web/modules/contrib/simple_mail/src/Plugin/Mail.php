<?php

namespace Drupal\simple_mail\Plugin\Mail;

use Drupal\Core\Mail\MailInterface;

/**
 * Simple Mail mail backend.
 *
 * @Mail(
 *   id = "simple_mail_mailer"
 *   label = @Translation("Simple Mail mailer")
 *   description = @Translation("Simple Mail mail backend.")
 * )
 */
class SimpleMailMailer extends MailInterface {
  /**
   * Format a message.
   */
  public function format(array $message) {
    $message['body'] = implode("\n\n", $message['body']);
    $message['body'] = drupal_wrap_mail($message['body']);
    return $message;
  }
}
