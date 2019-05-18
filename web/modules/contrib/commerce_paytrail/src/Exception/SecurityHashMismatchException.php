<?php

namespace Drupal\commerce_paytrail\Exception;

/**
 * Class SecurityHashMismatchException.
 *
 * @package Drupal\commerce_paytrail\Exception
 */
class SecurityHashMismatchException extends \Exception {

  protected $reason;

  /**
   * Constructs a new instance.
   *
   * @param string $reason
   *   The reason.
   * @param string $message
   *   The message.
   * @param int $code
   *   The error code.
   * @param \Throwable|null $previous
   *   The throwable.
   */
  public function __construct(
    string $reason = '',
    $message = "",
    $code = 0,
    \Throwable $previous = NULL
  ) {
    $this->reason = $reason;

    parent::__construct($message, $code, $previous);
  }

  /**
   * Gets the reason.
   *
   * @return string
   *   The reason.
   */
  public function getReason() : string {
    return $this->reason;
  }

}
