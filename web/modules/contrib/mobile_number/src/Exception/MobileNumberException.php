<?php

namespace Drupal\mobile_number\Exception;

/**
 * Exception thrown during mobile number testing if the number is invalid.
 */
class MobileNumberException extends \RuntimeException {

  const ERROR_INVALID_NUMBER = 1;
  const ERROR_WRONG_TYPE = 2;
  const ERROR_WRONG_COUNTRY = 3;
  const ERROR_NO_NUMBER = 4;

}
