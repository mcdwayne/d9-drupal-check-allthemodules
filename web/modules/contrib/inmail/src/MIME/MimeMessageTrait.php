<?php

namespace Drupal\inmail\MIME;

use Drupal\Component\Datetime\DateTimePlus;

/**
 * Provides common helper methods for MultiPartMessage.
 */
trait MimeMessageTrait {

  /**
   * An associative array of keys and corresponding error messages.
   *
   * It contains information that is provided by validate function.
   *
   * @var array
   */
  protected $validationErrors = [];

  /**
   * Returns the header of the entity.
   *
   * @see \Drupal\inmail\MIME\MimeEntityInterface
   *
   * @return \Drupal\inmail\MIME\MimeHeader
   *   The header.
   */
  abstract public function getHeader();

  /**
   * {@inheritdoc}
   */
  public function getFrom() {
    return $this->parseDecodeField('From');
  }

  /**
   * {@inheritdoc}
   */
  public function getTo() {
    return $this->parseDecodeField('To');
  }

  /**
   * {@inheritdoc}
   */
  public function getCc() {
    return $this->parseDecodeField('Cc');
  }

  /**
   * {@inheritdoc}
   */
  public function getBcc() {
    return $this->parseDecodeField('Bcc');
  }

  /**
   * {@inheritdoc}
   */
  public function getReplyTo() {
    return $this->parseDecodeField('Reply-To');
  }

  /**
   * Parses address field and decodes on request.
   *
   * @param $name
   *   The field name.
   *
   * @return \Drupal\inmail\MIME\Rfc2822Address[]
   *   A list of the parsed address objects.
   */
  protected function parseDecodeField($name) {
    $body = $this->getHeader()->getFieldBody($name);
    return MimeParser::parseAddress($body);
  }

  /**
   * {@inheritdoc}
   */
  public function getReceivedDate() {
    // A message can have one or more Received header fields. The first
    // occurring is the latest added. Its body has two parts separated by ';',
    // the second part being a date.
    if (!$received_body = $this->getHeader()->getFieldBody('Received')) {
      return NULL;
    }
    list($info, $date_string) = explode(';', $received_body, 2);
    return $this->parseTimezone($date_string);
  }

  /**
   * {@inheritdoc}
   */
  public function getDate() {
    $date_string = $this->getHeader()->getFieldBody('Date');
    return $this->parseTimezone($date_string);
  }

  /**
   * Returns cleaned and parsed date-time object.
   *
   * @param string $date_string.
   *   The date string.
   *
   * @return \Drupal\Component\DateTime\DateTimePlus
   *   The date object without time zone abbreviation.
   */
  protected function parseTimezone($date_string) {
    // By RFC2822 time-zone abbreviation is invalid and needs to be removed.
    // Match only capital letters within the brackets at the end of string.
    $date_string = preg_replace('/\(([A-Z]+)\)$/', '', $date_string);
    return new DateTimePlus($date_string);
  }

  /**
   * Checks that message complies with RFC standards.
   *
   * Implementations must make sure getValidationErrors() returns any errors
   * found.
   *
   * @return bool
   *   Returns TRUE if valid, otherwise FALSE.
   */
  public function validate() {
    $valid = TRUE;
    // RFC 5322 specifies Date and From header fields as only required fields.
    // @See https://tools.ietf.org/html/rfc5322#section-3.6
    foreach (['Date', 'From'] as $field_name) {
      // If the field is absent, set the validation error.
      if (!$this->getHeader()->hasField($field_name)) {
        $this->setValidationError($field_name, "Missing $field_name field.");
        $valid = FALSE;
      }
      // There should be only one occurrence of Date and From fields.
      elseif (($count = count($this->getHeader()->getFieldBodies($field_name))) > 1) {
        $this->setValidationError($field_name, "Only one occurrence of $field_name field is allowed. Found $count.");
        $valid = FALSE;
      }
    }
    return $valid;
  }

  /**
   * Returns validation error messages.
   *
   * @return string[]
   *   Associative array with keys and related error messages, or an empty array
   *   if there are no errors.
   */
  public function getValidationErrors() {
    return $this->validationErrors;
  }

  /**
   * Sets a validation error for the given header field.
   *
   * @param string $field
   *   The header field.
   * @param string $error
   *   The error message.
   */
  public function setValidationError($field, $error) {
    $this->validationErrors[$field] = $error;
  }

}
