<?php

namespace Drupal\inmail\MIME;

/**
 * A value object for RFC 2822 email message addresses.
 *
 * @ingroup mime
 */
class Rfc2822Address implements \JsonSerializable {

  /**
   * The optional display name before the address.
   *
   * @var string
   */
  protected $name;

  /**
   * The email address.
   *
   * @var string
   */
  protected $address;

  /**
   * Constructs a Rfc2822Address object.
   *
   * @param string $name
   *   The optional name before the address.
   * @param string $address
   *   The email address.
   */
  public function __construct($name, $address) {
    $this->name = $name;
    $this->address = $address;
  }

  /**
   * Gets the mailbox display name.
   *
   * @return string
   *   The optional name before the address. Empty string if it's not provided.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Gets the mailbox address.
   *
   * @return string
   *   The email address.
   */
  public function getAddress() {
    return $this->address;
  }

  /**
   * Gets the decoded UTF8 email address.
   *
   * @return string
   *   The decoded email address.
   */
  public function getDecodedAddress() {
    return $this->decodeAddress($this->address);
  }

  /**
   * Converts an email address from Puny-code to UTF8.
   *
   * @param string $address
   *   The email address to be decoded.
   *
   * @return string|null
   *   The UTF8 decoded address if successful decoding, otherwise NULL.
   */
  protected function decodeAddress($address) {
    // Skip decoding if the intl package is missing.
    if (!function_exists('idn_to_utf8')) {
      return $address;
    }

    if (strpos($address, '@') !== FALSE) {
      // Extract address domain after '@' sign for proper IDN decoding.
      $address = explode('@', $address, 2)[0] . '@' . \idn_to_utf8(explode('@', $address, 2)[1]);
    }
    return $address;
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() {
    return get_object_vars($this);
  }

}
