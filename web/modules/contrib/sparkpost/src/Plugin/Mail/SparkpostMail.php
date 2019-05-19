<?php

namespace Drupal\sparkpost\Plugin\Mail;

use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\MailInterface;
use Drupal\Component\Utility\Html;
use Drupal\sparkpost\ClientService;

/**
 * Class SparkpostMail.
 *
 * @Mail(
 *   id = "sparkpost_mail",
 *   label = @Translation("Sparkpost mailer"),
 *   description = @Translation("Sends the message through Sparkpost.")
 * )
 */
class SparkpostMail implements MailInterface {

  /**
   * Immutable config service.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Message wrapper.
   *
   * @var \Drupal\sparkpost\MessageWrapperInterface
   */
  protected $messageWrapper;

  /**
   * SparkpostMail constructor.
   */
  public function __construct() {
    $this->config = \Drupal::service('config.factory')->get('sparkpost.settings');
    $this->messageWrapper = \Drupal::service('sparkpost.message_wrapper');
  }

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    // Join the body array into one string.
    if (is_array($message['body'])) {
      $message['body'] = implode("\n\n", $message['body']);
    }

    // Remove newlines.
    $message['subject'] = preg_replace('/[\r\n]+/', '', $message['subject']);

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    try {
      $format = $this->config->get('format');
      if (!empty($format)) {
        $message['body'] = check_markup($message['body'], $format);
      }
      // @todo: Use format from settings when implemented.
      // Prepare headers, defaulting the reply-to to the from address since
      // Sparkpost needs the from address to be configured separately.
      // Note that only Reply-To and X-* headers are allowed.
      $headers = isset($message['headers']) ? $message['headers'] : [];
      if (isset($message['params']['sparkpost']['header'])) {
        $headers = $message['params']['sparkpost']['header'] + $headers;
      }
      if (!empty($message['from']) && empty($headers['Reply-To'])) {
        $headers['Reply-To'] = $message['from'];
      }

      // Extract an array of recipients.
      $to = $this->createRecipientField($message['to']);
      // Prepare list of Cc and Bcc recipients.
      $cc = $bcc = [];
      if (isset($headers['Cc'])) {
        $cc = $this->createCcField($headers['Cc'], $to);
      }
      if (isset($headers['Bcc'])) {
        $bcc = $this->createCcField($headers['Bcc'], $to);
      }
      $to = array_merge($to, $cc, $bcc);

      $reply_to = isset($headers['Reply-To']) ? $headers['Reply-To'] : NULL;
      $headers = $this->allowedHeaders($headers);

      // Prepare attachments.
      $attachments = [];
      if (isset($message['attachments']) && !empty($message['attachments'])) {
        foreach ($message['attachments'] as $attachment) {
          if (is_file($attachment)) {
            $attachments[] = $this->getAttachmentStruct($attachment);
          }
        }
      }

      // @todo: Check what the mime-mail situation in d8 is, and add fallback for
      // that here.
      $plain_text = empty($message['params']['plaintext']) ? MailFormatHelper::htmlToText($message['body']) : $message['params']['plaintext'];
      $overrides = isset($message['params']['sparkpost']['overrides']) ? $message['params']['sparkpost']['overrides'] : [];
      $sparkpost_message = $overrides + [
        'content' => [
          'from' => [
            'name' => $this->config->get('sender_name'),
            'email' => $this->config->get('sender'),
          ],
          'html' => $message['body'],
          'text' => $plain_text,
          'subject' => $message['subject'],
          'attachments' => $attachments,
          'reply_to' => $reply_to,
          'headers' => $headers,
        ],
        'recipients' => $to,
        // SparkPost doesn't allow campaigns longer than 64 characters.
        'campaign_id' => substr($message['id'], 0, 64),
        'options' => [
          'transactional' => TRUE,
        ],
      ];

      // @todo: Handle response in some way.
      \Drupal::moduleHandler()->alter('sparkpost_mail', $sparkpost_message, $message);
      $this->messageWrapper->setDrupalMessage($message);
      $this->messageWrapper->setSparkpostMessage($sparkpost_message);
      if ($this->config->get('async')) {
        $queue = \Drupal::queue('sparkpost_send');
        $queue->createItem($this->messageWrapper);
        return TRUE;
      }
      $this->messageWrapper->sendMessage();
      return TRUE;
    }
    catch (\Exception $e) {
      if ($this->config->get('debug')) {
        watchdog_exception('sparkpost', $e);
      }
      return FALSE;
    }
  }

  /**
   * Return an array structure for a message attachment.
   *
   * @param string $path
   *   Attachment path.
   *
   * @return array
   *   Attachment structure.
   *
   * @throws \Exception
   */
  protected function getAttachmentStruct($path) {
    $struct = [];

    if (!@is_file($path)) {
      throw new \Exception($path . ' is not a valid file.');
    }

    $filename = basename($path);

    $file_buffer = file_get_contents($path);
    $file_buffer = chunk_split(base64_encode($file_buffer), 76, "\n");

    $mime_type = \Drupal::service('file.mime_type.guesser')->guess($path);
    if (!$this->isValidContentType($mime_type)) {
      throw new \Exception($mime_type . ' is not a valid content type.');
    }

    $struct['type'] = $mime_type;
    $struct['name'] = $filename;
    $struct['data'] = $file_buffer;

    return $struct;
  }

  /**
   * Helper to determine if an attachment is valid.
   *
   * @param string $file_type
   *   The file mime type.
   *
   * @return bool
   *   True or false.
   */
  protected function isValidContentType($file_type) {
    $valid_types = [
      'image/',
      'text/',
      'application/pdf',
      'application/x-zip',
    ];
    // @todo: Make this alterable.

    foreach ($valid_types as $vct) {
      if (strpos($file_type, $vct) !== FALSE) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Limit headers to SparkPost whitelist.
   *
   * @param array $headers
   *   Array of headers.
   *
   * @return array
   *   Array of headers.
   */
  private function allowedHeaders(array $headers) {
    foreach ($headers as $header => $value) {
      if (strpos($header, 'X-') === 0) {
        continue;
      }
      elseif (in_array($header, ['Return-Path', 'Cc'])) {
        // Only Return-Path and Cc are supported.
        // Bcc recipients will be added to recipient list automatically and
        // removed from here.
        continue;
      }
      unset($headers[$header]);
    }
    return $headers;
  }

  /**
   * Helper to generate an array of recipients.
   *
   * @param mixed $to
   *   A comma delimited list of email addresses in 1 of 2 forms:
   *   user@domain.com
   *   any number of names <user@domain.com>.
   *
   * @return array
   *   array of email addresses
   */
  private function createRecipientField($to) {
    $recipients = [];
    $to_array = explode(',', $to);
    foreach ($to_array as $email) {
      if (preg_match(ClientService::EMAIL_REGEX, $email, $matches)) {
        $recipients[] = [
          'address' => [
            'name' => $matches[1],
            'email' => $matches[2],
          ],
        ];
      }
      else {
        $recipients[] = ['address' => ['email' => $email]];
      }
    }

    return $recipients;
  }

  /**
   * Get list of cc recipients.
   *
   * @param string $cc
   *   Comma separated list of Cc recipients.
   * @param array $to
   *   List of recipients created by sparkpost_get_to().
   *
   * @return array
   *   List of recipients to merged with sparkpost_get_to() results.
   */
  private function createCcField($cc, array $to) {
    $recipients = [];

    // Explode recipient list.
    $cc_array = explode(',', $cc);

    // Prepare header_to value.
    $header_to = implode(',', array_map(function ($recipient) {
      return $recipient['address']['email'];
    }, $to));

    foreach ($cc_array as $email) {
      if (preg_match(ClientService::EMAIL_REGEX, $email, $matches)) {
        $email = $matches[2];
      }
      $recipients[] = [
        'address' => [
          'email' => $email,
          'header_to' => $header_to,
        ],
      ];
    }

    return $recipients;
  }

}
