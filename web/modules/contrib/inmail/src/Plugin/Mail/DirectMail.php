<?php

namespace Drupal\inmail\Plugin\Mail;

use Drupal\Core\Mail\MailInterface;

/**
 * Sends a message with the native mail() function and without modification.
 *
 * This allows forwarding of a message similar to MTA behaviour.
 * @see Drupal\inmail\Plugin\inmail\Handler\ModeratorForwardHandler
 *
 * DirectMail is needed because PhpMail modifies header contents.
 * When forwarding, mail headers need to be preserved in sequence and can
 * contain the same key multiple times.
 * @see Drupal\Core\Mail\Plugin\Mail\PhpMail
 *
 * @ingroup handler
 *
 * @Mail(
 *   id = "inmail_direct",
 *   label = @Translation("Direct"),
 *   description = @Translation("Sends a message with the native mail() function and without modification.")
 * )
 */
class DirectMail implements MailInterface {

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    // Join the body array into one string.
    $message['body'] = implode("\n\n", $message['body']);

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    // Headers are passed in $message['raw_headers'], see explanation in
    // inmail_mail().

    $headers = NULL;
    if (isset($message['raw_headers'])) {
      // Cleanup the Subject as it's added when sending.
      $message['raw_headers']->removeField('Subject');
      $headers = $message['raw_headers']->toString();
    }

    return (bool) mail(
      $message['to'],
      $message['subject'],
      $message['body'],
      $headers
    );
  }
}
