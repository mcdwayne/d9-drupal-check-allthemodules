<?php

namespace Drupal\postmark;

use Drupal\Core\Config\ConfigFactoryInterface;
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;
use Psr\Log\LoggerInterface;

/**
 * Mail handler to send out an email message array to the Postmark API.
 */
class PostmarkHandler {

  /**
   * Configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Postmark client.
   *
   * @var \Postmark\PostmarkClient
   */
  protected $postmarkClient;

  /**
   * Constructs a new PostmarkHandler object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(ConfigFactoryInterface $configFactory, LoggerInterface $logger) {
    $this->config = $configFactory->get('postmark.settings');
    $this->logger = $logger;
    $this->postmarkClient = new PostmarkClient($this->config->get('postmark_api_key'));
  }

  /**
   * Connects to Postmark API and sends out the email.
   *
   * @param array $params
   *   A message array.
   *
   * @return bool
   *   TRUE if the mail was successfully accepted by the API, FALSE otherwise.
   */
  public function sendMail(array $params) {
    $api_key = $this->config->get('postmark_api_key');
    $debug_mode = $this->config->get('postmark_debug_mode');
    $debug_email = $debug_mode ? $this->config->get('postmark_debug_email') : FALSE;
    $sender_signature = $this->config->get('postmark_sender_signature');

    try {
      if (self::checkApiSettings($api_key, $sender_signature) === FALSE) {
        $this->logger->error('Failed to send message from %from to %to. Please check the Postmark settings.',
          [
            '%from' => $sender_signature,
            '%to' => $params['to'],
          ]
        );
        return FALSE;
      }

      if ($this->config->get('postmark_debug_no_send')) {
        drupal_set_message('Email successfully tested, no email has been sent (no credits used).', 'warning');
        return TRUE;
      }

      $html = !empty($params['html']) && empty($params['text']) ? $params['html'] : NULL;
      $text = !empty($params['text']) ? $params['text'] : NULL;
      $tag = NULL;
      $track_opens = FALSE;
      $reply_to = !empty($params['reply-to']) ? $params['reply-to'] : NULL;
      $cc = !empty($params['cc']) ? $params['cc'] : NULL;
      $bcc = !empty($params['bcc']) ? $params['cc'] : NULL;

      $response = $this->postmarkClient->sendEmail(
        $sender_signature,
        $debug_email ? $debug_email : $params['to'],
        $params['subject'],
        $html,
        $text,
        $tag,
        $track_opens,
        $reply_to,
        $cc,
        $bcc
      );

      // Debug mode: log all messages.
      if ($debug_mode) {
        $this->logger->notice('Successfully sent message from %from to %to. Response data was %response.',
          [
            '%from' => $sender_signature,
            '%to' => $params['to'],
            '%response' => '<pre>' . print_r($response->dumpAvailable, 1) . '</pre>',
          ]
        );
      }
    }
    catch (PostmarkException $e) {
      // If client is able to communicate with the API in a timely fashion,
      // but the message data is invalid, or there's a server error,
      // a PostmarkException can be thrown.
      $this->logger->error('Postmark exception occurred while trying to send email from %from to %to. @code: @message',
        [
          '%from' => $sender_signature,
          '%to' => $params['to'],
          '@code' => $e->postmarkApiErrorCode,
          '@message' => $e->message,
        ]
      );
      return FALSE;
    }
    catch (Exception $e) {
      // A general exception is thrown if the API
      // was unreachable or times out.
      $this->logger->error('General exception occurred while trying to send Postmark email from %from to %to. @code: @message',
        [
          '%from' => $sender_signature,
          '%to' => $params['to'],
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
        ]
      );
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Check that the Postmark PHP API is installed correctly.
   */
  public static function checkLibrary($showMessage = FALSE) {
    $libraryStatus = class_exists('\Postmark\PostmarkClient');
    if ($showMessage === FALSE) {
      return $libraryStatus;
    }

    if ($libraryStatus === FALSE) {
      drupal_set_message(t('The Postmark PHP library has not been installed correctly.'), 'warning');
    }
    return $libraryStatus;
  }

  /**
   * Check if API settings are correct and not empty.
   */
  public static function checkApiSettings($key, $signature, $showMessage = FALSE) {
    if (empty($key) || empty($signature)) {
      if ($showMessage) {
        drupal_set_message(t('Please check your Postmark settings. API token and Sender Signature must not be empty.'), 'warning');
      }
      return FALSE;
    }

    if (self::validateKey($key) === FALSE) {
      if ($showMessage) {
        drupal_set_message(t('Unable to connect to the Postmark API. Please check your API settings.'), 'warning');
      }
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Validates Postmark API key.
   */
  public static function validateKey($key) {
    if (self::checkLibrary() === FALSE) {
      return FALSE;
    }

    try {
      $postmark = new PostmarkClient($key);
    }
    catch (Exception $e) {
      return FALSE;
    }
    return TRUE;
  }

}
