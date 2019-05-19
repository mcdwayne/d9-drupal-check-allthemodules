<?php


namespace Drupal\x_reference\Exception;

/**
 * Class XReferenceTypeNotFoundException
 *
 * @package Drupal\x_reference\Exception
 */
class XReferenceTypeNotFoundException extends \RuntimeException {

  /**
   * Construct an XReferenceTypeNotFoundException exception.
   *
   * For the remaining parameters see \Exception.
   *
   * @param string $XReferenceType
   *   The XReferenceType that was not found.
   *
   * @param string $message
   * @param int $code
   * @param \Exception|null $previous
   *
   * @see \Exception
   */
  public function __construct($XReferenceType, $message = '', $code = 0, \Exception $previous = NULL) {
    if (empty($message)) {
      $message = sprintf("X-reference type '%s' was not found.", $XReferenceType);
    }
    parent::__construct($message, $code, $previous);
  }

}
