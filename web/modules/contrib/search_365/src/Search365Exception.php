<?php

namespace Drupal\search_365;

use RuntimeException;
use Throwable;

/**
 * Exception thrown for any Search365 errors.
 *
 * @codingStandardsIgnoreFile
 */
class Search365Exception extends RuntimeException {

  /**
   * Search365Exception constructor.
   *
   * @param string $message
   *   (optional) The Exception message to throw.
   * @param int $code
   *   (optional) The Exception code.
   * @param Throwable $previous
   *   (optional) The previous throwable used for the exception chaining.
   */
  public function __construct($message = "", $code = 0, Throwable $previous = NULL) {
    parent::__construct($message, $code, $previous);
  }

}
