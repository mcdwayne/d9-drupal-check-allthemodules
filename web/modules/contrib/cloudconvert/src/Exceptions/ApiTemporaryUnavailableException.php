<?php

namespace Drupal\cloudconvert\Exceptions;

/**
 * ApiBadRequestException exception is thrown.
 *
 * When a the CloudConvert API returns any HTTP error code 503.
 *
 * @package CloudConvert
 * @category Exceptions
 * @author Josias Montag <josias@montag.info>
 */
class ApiTemporaryUnavailableException extends ApiException {

  /**
   * Number of seconds when to retry.
   *
   * @var int
   */
  public $retryAfter = 0;

  /**
   * ApiTemporaryUnavailableException constructor.
   *
   * @param string $msg
   *   Response message.
   * @param string $code
   *   Response code.
   * @param int $retryAfter
   *   Retry after in seconds.
   */
  public function __construct($msg, $code, $retryAfter = 0) {
    $this->retryAfter = $retryAfter;
    parent::__construct($msg, $code);
  }

}
