<?php

namespace Drupal\swiftmailer_force_html\Plugin\Mail;

use Drupal\Core\Render\Markup;
use Drupal\Core\Site\Settings;
use Drupal\swiftmailer\Plugin\Mail\SwiftMailer;

/**
 * Provides a 'SwiftMailer (Force HTML)' plugin to send emails.
 *
 * @Mail(
 *   id = "swiftmailer_html",
 *   label = @Translation("Swift Mailer (Force HTML)"),
 *   description = @Translation("Forces the given body text to be interpreted as HTML.")
 * )
 */
class SwiftMailerHtml extends SwiftMailer {

  /**
   * Massages the message body into the format expected for rendering.
   *
   * @param array $message
   *   The message.
   *
   * @return array
   *   The massaged message.
   */
  public function massageMessageBody(array $message) {
    // @see: SwiftMailer::massageMessageBody()
    $line_endings = Settings::get('mail_line_endings', PHP_EOL);

    $message['body'] = Markup::create(implode($line_endings, array_map(function ($body) {
      // If the field contains no html tags,
      // we can assume newlines will need be converted to <br>.
      if (strlen(strip_tags($body)) === strlen($body)) {
        $body = nl2br($body);
      }

      return check_markup($body, 'full_html');
    }, $message['body'])));

    return $message;
  }

}
