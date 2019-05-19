<?php

namespace Drupal\twilio\Services;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
use Drupal\Component\Utility\UrlHelper;

/**
 * Service class for Twilio API commands.
 */
class Command {

  private $sid;
  private $token;
  private $number;

  /**
   * Initialize properties.
   */
  public function __construct() {
    $this->sid = $this->getSid();
    $this->token = $this->getToken();
    $this->number = $this->getNumber();
  }

  /**
   * Get the Twilio Account SID.
   *
   * @return string
   *   The configured Twilio Account SID.
   */
  public function getSid() {
    if (empty($this->sid)) {
      $this->sid = \Drupal::config('twilio.settings')->get('account');
    }
    return $this->sid;
  }

  /**
   * Get the Twilio Auth Token.
   *
   * @return string
   *   The configured Twilio Auth Token.
   */
  public function getToken() {
    if (empty($this->token)) {
      $this->token = \Drupal::config('twilio.settings')->get('token');
    }
    return $this->token;
  }

  /**
   * Get the Twilio Number.
   *
   * @return string
   *   The configured Twilio Number.
   */
  public function getNumber() {
    if (empty($this->number)) {
      $this->number = \Drupal::config('twilio.settings')->get('number');
    }
    return $this->number;
  }

  /**
   * Send an SMS message.
   *
   * @param string $number
   *   The number to send the message to.
   * @param string|array $message
   *   Message text or an array:
   *   [
   *     from => number
   *     body => message string
   *     mediaUrl => absolute URL
   *   ].
   */
  public function messageSend(string $number, $message) {
    if (is_string($message)) {
      $message = [
        'from' => $this->number,
        'body' => $message,
      ];
    }
    $message['from'] = !empty($message['from']) ? $message['from'] : $this->number;
    if (empty($message['body'])) {
      throw new TwilioException("Message requires a body.");
    }
    if (!empty($message['mediaUrl']) && !UrlHelper::isValid($message['mediaUrl'], TRUE)) {
      throw new TwilioException("Media URL must be a valid, absolute URL.");
    }
    $client = new Client($this->sid, $this->token);
    $client->messages->create($number, $message);
  }

}
