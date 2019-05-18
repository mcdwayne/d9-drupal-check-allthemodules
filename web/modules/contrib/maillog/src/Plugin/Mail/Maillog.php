<?php
/**
 * @file
 * Contains \Drupal\mailsystem\Plugin\mailsystem\Dummy.
 */

namespace Drupal\maillog\Plugin\Mail;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\Database;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Drupal\Core\Url;

/**
 * Provides a 'Dummy' plugin to send emails.
 *
 * @Mail(
 *   id = "maillog",
 *   label = @Translation("Maillog Mail-Plugin"),
 *   description = @Translation("Maillog Mail-Plugin for sending and formating complete mails.")
 * )
 */
class Maillog implements MailInterface {

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    $default = new PhpMail();
    return $default->format($message);
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    $config = \Drupal::configFactory()->get('maillog.settings');
    // Log the e-mail
    if ($config->get('log')) {
      $record = new \stdClass;

      // In case the subject/from/to is already encoded, decode with
      // Unicode::mimeHeaderDecode().
      $record->header_message_id = isset($message['headers']['Message-ID']) ? $message['headers']['Message-ID'] : NULL;
      $record->subject = $message['subject'];
      $record->subject = Unicode::substr(Unicode::mimeHeaderDecode($record->subject), 0, 255);
      $record->body = $message['body'];
      $record->header_from = isset($message['from']) ? $message['from'] : NULL;
      $record->header_from = Unicode::mimeHeaderDecode($record->header_from);

      $header_to = array();
      if (isset($message['to'])) {
        if (is_array($message['to'])) {
          foreach ($message['to'] as $value) {
            $header_to[] = Unicode::mimeHeaderDecode($value);
          }
        }
        else {
          $header_to[] = Unicode::mimeHeaderDecode($message['to']);
        }
      }
      $record->header_to = implode(', ', $header_to);

      $record->header_reply_to = isset($message['headers']['Reply-To']) ? $message['headers']['Reply-To'] : '';
      $record->header_all = serialize($message['headers']);
      $record->sent_date = REQUEST_TIME;

      Database::getConnection()->insert('maillog')
        ->fields((array) $record)
        ->execute();
    }

    // Display the e-mail if the verbose is enabled.
    if ($config->get('verbose') && \Drupal::currentUser()->hasPermission('view maillog')) {

      // Print the message.
      $header_output = print_r($message['headers'], TRUE);
      $output = t('A mail has been sent: <br/> [Subject] => @subject <br/> [From] => @from <br/> [To] => @to <br/> [Reply-To] => @reply <br/> <pre>  [Header] => @header <br/> [Body] => @body </pre>', [
        '@subject' => $message['subject'],
        '@from' => $message['from'],
        '@to' => $message['to'],
        '@reply' => isset($message['reply_to']) ? $message['reply_to'] : NULL,
        '@header' => $header_output,
        '@body' => $message['body']
      ]);
      drupal_set_message($output, 'status', TRUE);
    }

    if ($config->get('send')) {
      $default = new PhpMail();
      $result = $default->mail($message);
    }
    elseif (\Drupal::currentUser()->hasPermission('administer maillog')) {
      $message = t('Sending of e-mail messages is disabled by Maillog module. Go @here to enable.', ['@here' => \Drupal::l('here', Url::fromRoute('maillog.settings'))]);

      drupal_set_message($message, 'warning', TRUE);
    }
    else {
      \Drupal::logger('maillog')->notice('Attempted to send an email, but sending emails is disabled.');
    }
    return isset($result) ? $result : TRUE;
  }
}
