<?php

namespace Drupal\uc_store\Plugin\Mail;

use Drupal\Core\Mail\Plugin\Mail\TestMailCollector;

/**
 * Defines an HTML-capable mail backend that captures sent messages for tests.
 *
 * @Mail(
 *   id = "test_html_mail_collector",
 *   label = @Translation("HTML mailer for testing"),
 *   description = @Translation("Does not send the message, but stores it in Drupal within the state system. Used for testing HTML messages.")
 * )
 */
class TestHtmlMailCollector extends TestMailCollector {

  /**
   * Overrides PhpMail::format() to prevent it from stripping out the HTML.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return string
   *   The formatted $message.
   */
  public function format(array $message) {
    $message['body'] = implode("\n\n", $message['body']);
    return $message;
  }

}
