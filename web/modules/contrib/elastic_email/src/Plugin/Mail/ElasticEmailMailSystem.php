<?php
namespace Drupal\elastic_email\Plugin\Mail;

use Drupal\Core\Mail\MailInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\elastic_email\Service\ElasticEmailManager;
use ElasticEmailClient\ApiException;

/**
 * Modify the drupal mail system to use Elastic Email to send emails.
 *
 * @Mail(
 *   id = "elastic_email_mail",
 *   label = @Translation("Elastic Email Mailer"),
 *   label_singular = @Translation("Elastic Email Mailer"),
 *   label_plural = @Translation("Elastic Email Mailers"),
 *   label_count = @PluralTranslation(
 *     singular = @Translation("elastic email mailer"),
 *     plural = @Translation("elastic email mailers")
 *   ),
 *   description = @Translation("Sends emails via Elastic Email.")
 * )
 */
class ElasticEmailMailSystem implements MailInterface {
  use StringTranslationTrait;

  /**
   * Concatenate and wrap the e-mail body for either plain-text or HTML emails.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return string
   *   The formatted $message.
   */
  public function format(array $message) {
    // Join the body array into one string.
    $message['body'] = implode("\n\n", $message['body']);

    return $message;
  }

  /**
   * Send the e-mail message.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return bool
   *   TRUE if the mail was successfully accepted, otherwise FALSE.
   *
   * @see \Drupal\Core\Mail\MailManagerInterface::mail()
   */
  public function mail(array $message) {
    // If queueing is available and enabled, queue the message.
    if (\Drupal::config('elastic_email.settings')->get('queue_enabled')) {
      $queue = \Drupal::queue('elastic_email_process_queue');
      $queue->createItem($message);
      $queue->createQueue();

      \Drupal::logger('elastic_email_queue')->info('Message added to the Queue - no. of messages: ' . $queue->numberOfItems(), []);

      return $this->t('Email message queued for delivery via Elastic Email at cron time.');
    }
    else {
      // Otherwise send the message directly.
      $this->send($message);
      return $this->t('Queuing unavailable. Email sent directly via Elastic Email.');
    }
  }

  /**
   * Performs the actual sending of the message to the Elastic Email service. This
   * code was originally based on the'sendElasticEmail' code snippet from the
   * Elastic Email website.
   *
   * The $elastic_username and $api_key variables are generally not required, as
   * they they are specified in the module configuration. However, you may supply
   * these parameters if you wish to override the configuration values.
   *
   * You must provide either the $subject or $body_text parameter. That is, it is
   * not possible to send an empty email.
   *
   * If this method succeeds, an array will be returned with the following
   * elements:
   * - array['success']['tx_id']: the transaction id returned by Elastic Email
   * - array['success']['to']: semi-colon separated list of recipients
   * - array['success']['msg']: user-friendly message
   *
   * If there's an error, an array will be returned with the following element:
   * - array['error'] : The error message returned by Elastic Email, or an error
   * message from this module if a pre-condition was not met (e.g. missing
   * required parameters).
   *
   * @param string $from
   *   The 'from' (sender) email address.
   * @param string $from_name
   *   (optional) The name of the sender. Defaults to NULL.
   * @param string $to
   *   The semi-colon separated list of recipient email addresses.
   * @param string $subject
   *   The subject line.
   * @param string $body_text
   *   The plain-text body of the message.
   * @param string $body_html
   *   The html-text body of the message.
   *
   * @return array
   *   Returns an array with either a 'success' or 'error' elements. See main
   *   function description for details. Note that the error message text will
   *   have already been passed through $this->t().
   *
   * @todo Provide support for HTML-based email and attachments?
   */
  public function elasticEmailSend($from, $from_name = NULL, $to, $subject = '', $body_text = NULL, $body_html = NULL) {
    $config = \Drupal::config('elastic_email.settings');
    $username = $config->get('username');
    $api_key = $config->get('api_key');

    $result = array();
    if (empty($username) || empty($api_key)) {
      $result['error'] = $this->t('Unable to send email to Elastic Email because username or API key not specified.');
    }
    elseif (empty($from) || empty($to) || (empty($subject) && empty($body_text))) {
      $result['error'] = $this->t('Unable to send email because some required email parameters are empty.');
    }

    if (isset($result['error'])) {
      return $result;
    }

    // Set the reply to if enabled.
    $use_reply_to = $config->get('use_reply_to');
    $reply_to = ($use_reply_to) ? $config->get('reply_to_email') : NULL;
    $reply_to_name = ($use_reply_to) ? $config->get('reply_to_name') : NULL;

    try {
      $defaultChannel = NULL;
      if ($config->get('use_default_channel')) {
        $defaultChannel = $config->get('default_channel');
      }

      $to = explode(';', $to);

      /** @var ElasticEmailManager $service */
      $service = \Drupal::service('elastic_email.api');
      $response = $service->getEmail()->Send(
        $subject,
        $from, $from_name,
        NULL, NULL,
        NULL, NULL,
        $reply_to, $reply_to_name,
        $to, $to, [], [],
        [], [], NULL,
        $defaultChannel,
        (!empty($body_html) ? $body_html : NULL),
        (!empty($body_text) ? $body_text : NULL)
      );
    }
    catch (ApiException $e) {
      drupal_set_message($e->getMessage(), 'error');
    }

    if (empty($response)) {
      $result['error'] = $this->t('Error: no response (or empty response) received from Elastic Email service.');
    }
    elseif (!preg_match('/[0-9a-z]{8}-[0-9a-z]{4}-[0-9a-z]{4}-[0-9a-z]{4}-[0-9a-z]{12}/', $response->transactionid)) {
      $result['error'] = 'error message'; //$response;
    }
    else {
      // Message was successfully delivered.
      $result['success']['msg'] = $this->t('Success [@tx_id]; message sent to: @recipients',
        [
          '@tx_id' => $response->transactionid,
          '@recipients' => $to
        ]);

      $result['success']['tx_id'] = $response->transactionid;
      $result['success']['recipients'] = $to;
    }

    return $result;
  }

  /**
   * Email sending function, called from job queue, or directly.
   *
   * @param array $message
   *   Standard Drupal email message object.
   *
   * @return bool
   *   TRUE if message delivered; FALSE otherwise.
   */
  public function send($message = array()) {
    // If there's no 'from', then use the default site email.
    if (empty($message['from'])) {
      $from = \Drupal::config('system.site')->get('mail');
      if (!empty($from)) {
        $message['from'] = $from;
      }
    }

    // Parse $message['from'] into $from and $from_name if in full RFC format.
    if (preg_match('~^"?([^"]+)"? <\s*(.+)\s*>$~', $message['from'], $matches)) {
      $from_name = $matches[1];
      $from = $matches[2];
    }
    else {
      $from_name = NULL;
      $from = $message['from'];
    }

    // Array to hold the set of email recipients.
    $recipients = array();

    // Parse the various fields that can contain email recipients.
    $this->parseRecipient($recipients, $message['to']);
    if (isset($message['headers']['Cc'])) {
      $this->parseRecipient($recipients, $message['headers']['Cc']);
    }
    if (isset($message['headers']['Bcc'])) {
      $this->parseRecipient($recipients, $message['headers']['Bcc']);
    }

    // Concatenate recipients to a semi-colon separated string.
    $to = '';
    if (count($recipients)) {
      $to = implode('; ', $recipients);
    }

    // Check the header content type to see if email is plain text, if not we
    // send as HTML.
    $is_html = (strpos($message['headers']['Content-Type'], 'text/plain') === FALSE);

    // Attempt to send the message.
    $body_text = ($is_html ? NULL : $message['body']);
    $body_html = ($is_html ? $message['body'] : NULL);
    $result = $this->elasticEmailSend($from, $from_name, $to, $message['subject'], $body_text, $body_html);

    if (isset($result['error'])) {
      // If there's an error, log it.
      \Drupal::logger('elastic_email')->critical('Failed to send email.  Reason: ' . $result['error'], []);
    }

    if (\Drupal::config('system.site')->get('log_success')) {
      // If success, only log if the ELASTIC_EMAIL_LOG_SUCCESS variable is TRUE.
      if (isset($result['success'])) {
        \Drupal::logger('elastic_email')->info('Email sent successfully: ' . $result['success']['msg'], []);
      }
    }

    return isset($result['success']) && $result['success'] ? TRUE : FALSE;
  }

  /**
   * Given a comma-delimited list of email addresses in the $to parameter, parse
   * the addresses and add to the $recipients array.
   *
   * @param array $recipients
   *   A passed-by-reference array holding recipient email addresses.
   * @param string $to
   *   A comma-delimited list of email addresses.
   */
  protected function parseRecipient(&$recipients, $to) {
    if (!$to) {
      return;
    }

    // Trim any whitespace.
    $to = trim($to);
    if (!empty($to)) {
      // Explode on comma.
      $parts = explode(',', $to);
      foreach ($parts as $part) {
        // Again, trim any whitespace.
        $part = trim($part);
        if (!empty($part)) {
          $recipients[] = $part;
        }
      }
    }
  }
}
