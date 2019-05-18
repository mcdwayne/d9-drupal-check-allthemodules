<?php

namespace Drupal\business_rules\Plugin\Mail;

use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Mail\Plugin\Mail\PhpMail;

/**
 * Provides a 'Business Rules Mail' plugin to send emails.
 *
 * @Mail(
 *   id = "business_rules_mail",
 *   label = @Translation("Business Rules Mailer"),
 *   description = @Translation("Business Rules Mailer Plugin.")
 * )
 */
class BusinessRulesMail extends PhpMail implements MailInterface {

  /**
   * Formats a message composed by drupal_mail().
   *
   * @param array $message
   *   A message array holding all relevant details for the message.
   *
   * @return array
   *   The message as it should be sent.
   */
  public function format(array $message) {
    // Join the body array into one string.
    $message['body'] = implode("\n\n", $message['body']);

    if (!isset($message['headers']) || !isset($message['headers']['Content-Type']) || !stristr($message['headers']['Content-Type'], 'text/html')) {
      // Convert any HTML to plain-text.
      $message['body'] = MailFormatHelper::htmlToText($message['body']);
      // Wrap the mail body for sending.
      $message['body'] = MailFormatHelper::wrapMail($message['body']);
    }

    return $message;
  }


}
