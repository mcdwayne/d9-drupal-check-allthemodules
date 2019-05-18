<?php

namespace Drupal\phone_number\Exception;

use Exception;

/**
 * The phone number's country and the country provided do not match.
 */
class CountryException extends PhoneNumberException {

  /**
   * The invalid phone number's 2-letter country code.
   *
   * @var string
   */
  protected $country;

  /**
   * Constructs a new CountryException instance.
   *
   * @param string $message
   *   (optional) The Exception message to throw.
   * @param string $country
   *   (optional) The invalid phone number's 2-letter country code.
   * @param int $code
   *   (optional) The Exception code.
   * @param \Exception $previous
   *   (optional) The previous exception used for the exception chaining.
   */
  public function __construct($message = "", $country = NULL, $code = 0, Exception $previous = NULL) {
    parent::__construct($message, $code, $previous);

    $this->country = $country;
  }

  /**
   * Get the invalid phone number's country.
   *
   * @return string
   *   The invalid phone number's 2-letter country code.
   */
  public function getCountry() {
    return $this->country;
  }

}
