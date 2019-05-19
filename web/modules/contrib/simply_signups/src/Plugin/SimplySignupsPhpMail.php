<?php

namespace Drupal\simply_signups\Plugin\Mail;

use Drupal\Core\Mail\Plugin\Mail\PhpMail;

/**
 * Extend's the default Drupal mail backend to support HTML email.
 *
 * @Mail(
 *   id = "simply_signups_php_mail",
 *   label = @Translation("Simply Signups PHP mailer"),
 *   description = @Translation("Sends the message as HTML, using PHP's native mail() function.")
 * )
 */
class SimplySignupsPhpMail extends PhpMail {

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    $message['body'] = implode("\n\n", $message['body']);
    return $message;
  }

}
