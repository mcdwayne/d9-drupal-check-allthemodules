<?php

namespace Drupal\inmail_test\Plugin\Mail;

use Drupal\Core\Mail\Plugin\Mail\TestMailCollector;

/**
 * Works like Mail collector, but does not modify the message body.
 *
 * @see \Drupal\Core\Mail\Plugin\Mail\TestMailCollector
 *
 * @Mail(
 *   id = "inmail_test_mail_collector",
 *   label = @Translation("Inmail mail collector"),
 *   description = @Translation("Does not send nor format the message, but stores it in Drupal within the state system. Used for testing.")
 * )
 */
class InmailTestMailCollector extends TestMailCollector {

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    // Join the body array into one string.
    $message['body'] = implode("\n\n", $message['body']);

    return $message;
  }

}
