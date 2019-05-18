<?php

namespace Drupal\messagebird;

/**
 * Interface MessageBirdMessageInterface.
 *
 * @package Drupal\messagebird
 */
interface MessageBirdMessageInterface {

  /**
   * Initialize a new Message object.
   *
   * This will override any existing message in the object.
   *
   * @return $this
   */
  public function createNewMessage();

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
  public function setOriginator($sender);

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
  public function setRecipient($number);

  /**
   * Set the body of the SMS message.
   *
   * @param string $body
   *   The body of the SMS message.
   *
   * @return $this
   */
  public function setBody($body);

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
  public function setValidLifetime($seconds);

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
  public function setUnicode($bool);

  /**
   * Set the scheduled date and time of the message in RFC3339 format.
   *
   * @param string $datetime
   *   The date time string, formatted as 'Y-m-d\TH:i:sP'.
   *
   * @return $this
   */
  public function setScheduled($datetime = \DateTime::RFC3339);

  /**
   * Set the SMS route that is used to send the message.
   *
   * @param int $number
   *   The number of the gateway.
   *
   * @return $this
   */
  public function setGateway($number);

  /**
   * Send a SMS message.
   */
  public function sendSms();

  /**
   * Get the unique Id of the Message.
   */
  public function getId();

  /**
   * Get the URL of the Message info.
   */
  public function getHref();

  /**
   * Get the creation date and time of the Message.
   */
  public function getCreatedDateTime();

  /**
   * Get all the Recipients of the Message.
   *
   * @return array
   *   Recipients (msisdn as key) with their status values.
   */
  public function getRecipients();

}
