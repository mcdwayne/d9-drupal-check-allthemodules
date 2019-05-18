<?php

namespace Drupal\messagebird;

use Drupal\Core\Config\ConfigFactoryInterface;
use MessageBird\Objects\Message;

/**
 * Class MessageBirdMessage.
 *
 * @package Drupal\messagebird
 */
class MessageBirdMessage implements MessageBirdMessageInterface {

  /**
   * Configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * MessageBird Client.
   *
   * @var \MessageBird\Client
   */
  protected $client;

  /**
   * Exception object.
   *
   * @var \Drupal\messagebird\MessageBirdExceptionInterface
   */
  protected $exception;

  /**
   * MessageBird Message object.
   *
   * @var \MessageBird\Objects\Message
   */
  protected $message;

  /**
   * MessageBirdMessage constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Configuration Factory object.
   * @param \Drupal\messagebird\MessagebirdClientInterface $client
   *   MessageBird Client object.
   * @param \Drupal\messagebird\MessageBirdExceptionInterface $exception
   *   MessageBird Exception object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MessagebirdClientInterface $client, MessageBirdExceptionInterface $exception) {
    $this->config = $config_factory->get('messagebird.settings');
    $this->client = $client->getClient();
    $this->exception = $exception;
    $this->createNewMessage();
  }

  /**
   * Initialize a new Message object.
   *
   * This will override any existing message in the object.
   *
   * @return $this
   */
  public function createNewMessage() {
    $this->message = new Message();
    $this->message->datacoding = 'plain';
    $this->message->originator = $this->config->get('default.originator');

    return $this;
  }

  /**
   * The sender of the message.
   *
   * This can be a telephone number (including country code) or an alphanumeric
   * string. In case of an alphanumeric string, the maximum length is
   * 11 characters.
   *
   * @param string $sender
   *   Telephone number or 11 char string.
   *
   * @return $this
   */
  public function setOriginator($sender) {
    if (empty($sender)) {
      $sender = $this->config->get('default.originator');
    }
    $this->message->originator = $sender;

    return $this;
  }

  /**
   * Set a Recipient that needs to receive the SMS.
   *
   * @param int $number
   *   The 'msisdn' of the recipient.
   *
   * @see https://en.wikipedia.org/wiki/MSISDN#Example
   *
   * @return $this
   */
  public function setRecipient($number) {
    if (count($this->message->recipients) >= 50) {
      throw new \RuntimeException('Maximum number of recipients (50) has been reached.');
    }

    $this->message->recipients[] = (int) $number;

    return $this;
  }

  /**
   * Set the body of the SMS message.
   *
   * @param string $body
   *   The body of the SMS message.
   *
   * @return $this
   */
  public function setBody($body) {
    $this->message->body = $body;

    return $this;
  }

  /**
   * Set the amount of seconds that the message is valid.
   *
   * If a message is not delivered within this time,
   * the message will be discarded.
   *
   * @param int $seconds
   *   Number of seconds.
   *
   * @return $this
   */
  public function setValidLifetime($seconds) {
    $this->message->validity = $seconds;

    return $this;
  }

  /**
   * Set the characters to Unicode instead of Plain (GSM 03.38).
   *
   * Use unicode to be able to send all characters.
   * Every SMS will be sent Plain, unless Unicode has been set.
   *
   * @param bool $bool
   *   TRUE will set the datecode to Unicode, FALSE to GSM 03.38.
   *
   * @see https://en.wikipedia.org/wiki/GSM_03.38#GSM_7-bit_default_alphabet_and_extension_table_of_3GPP_TS_23.038_.2F_GSM_03.38
   *
   * @return $this
   */
  public function setUnicode($bool) {
    $this->message->datacoding = $bool ? 'unicode' : 'plain';

    return $this;
  }

  /**
   * Set the scheduled date and time of the message in RFC3339 format.
   *
   * @param string $datetime
   *   The date time string, formatted as 'Y-m-d\TH:i:sP'.
   *
   * @return $this
   */
  public function setScheduled($datetime = \DateTime::RFC3339) {
    $this->message->scheduledDatetime = $datetime;

    return $this;
  }

  /**
   * Set the SMS route that is used to send the message.
   *
   * @param int $number
   *   The number of the gateway.
   *
   * @return $this
   */
  public function setGateway($number) {
    $this->message->gateway = $number;

    return $this;
  }

  /**
   * Send a SMS message.
   *
   * After sending the message, this object will be overridden
   * with the values returned from MessageBird.
   * If you need to re-send an identical message,
   * clone the this object, before calling sendSMS().
   */
  public function sendSms() {
    $this->message->type = 'sms';

    try {
      // Send the SMS message.
      $this->client->messages->create($this->message);

      // Override the SMS message with the values returned from MessageBird.
      // If you need to re-send an identical message,
      // clone the Message object, before calling sendSMS().
      $this->message = $this->client->messages->getObject();

      // Handle debug information.
      $this->debugMessage();
    }
    catch (\Exception $e) {
      $this->exception->logError($e);
    }
  }

  /**
   * Get the body of the Message.
   *
   * @return string
   *   The body.
   */
  public function getBody() {
    return $this->message->body;
  }

  /**
   * Get the unique Id of the Message.
   */
  public function getId() {
    return $this->message->getId();
  }

  /**
   * Get the unique URL to MessageBird with information about the Message.
   *
   * @return string
   *   The URL of the Message.
   */
  public function getHref() {
    return $this->message->getHref();
  }

  /**
   * Get the originator of the Message.
   *
   * @return string
   *   The originator.
   */
  public function getOriginator() {
    return $this->message->originator;
  }

  /**
   * Get the creation date and time of the Message.
   *
   * @return string
   *   Date time formatted as RFC3339.
   */
  public function getCreatedDateTime() {
    return $this->message->getCreatedDatetime();
  }

  /**
   * Read a stored Message.
   *
   * @param string $id
   *   The Message Id.
   *
   * @return $this
   */
  public function readMessage($id) {
    try {
      $this->message = $this->client->messages->read($id);
      $this->debugMessage();
    }
    catch (\Exception $e) {
      $this->exception->logError($e);
    }

    return $this;
  }

  /**
   * Remove a stored Message.
   *
   * @param string $id
   *   The Message Id.
   */
  public function deleteMessage($id) {
    try {
      $this->message = $this->client->messages->delete($id);
    }
    catch (\Exception $e) {
      $this->exception->logError($e);
    }
  }

  /**
   * Get all the Recipients of the Message.
   *
   * @return array
   *   Recipients (msisdn as key) with their status values.
   */
  public function getRecipients() {
    $recipients = array();

    if (!($this->message->recipients instanceof \stdClass)) {
      throw new \RuntimeException('getRecipients() needs to have a successfully sended message.');
    }

    /** @var \MessageBird\Objects\Recipient $recipient */
    foreach ($this->message->recipients->items as $recipient) {
      $recipients[$recipient->recipient] = array(
        'status' => $recipient->status,
        'status_datetime' => $recipient->statusDatetime,
      );
    }

    return $recipients;
  }

  /**
   * Display Message information.
   */
  protected function debugMessage() {
    if ($this->config->get('debug.mode')) {

      $debug_callbacks = array(
        'getId' => t('Id'),
        'getHref' => t('Href'),
        'getBody' => t('Body'),
        'getOriginator' => t('Originator'),
        'getCreatedDateTime' => t('Created date time'),
        'getRecipients' => t('Recipients'),
      );

      foreach ($debug_callbacks as $callback => $title) {
        $value = call_user_func(array($this, $callback));
        if ($value) {
          drupal_set_message(t('@messagebird_debug_key: @messagebird_debug_value', array(
            '@messagebird_debug_key' => $title,
            '@messagebird_debug_value' => var_export($value, TRUE),
          )));
        }
      }
    }
  }

}
