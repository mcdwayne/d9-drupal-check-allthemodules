<?php


namespace Drupal\x_reference\Exception;


/**
 * Class XReferencedEntityNotSavedException
 *
 * @package Drupal\x_reference\Exception
 */
class XReferencedEntityNotSavedException extends \RuntimeException {

  /**
   * Construct an XReferencedEntityNotSavedException exception.
   *
   * @param string $message
   * @param int $code
   * @param \Exception|null $previous
   *
   * @see \Exception
   */
  public function __construct($message = '', $code = 0, \Exception $previous = NULL) {
    if (empty($message)) {
      $message = 'X-referenced entity is not saved';
    }
    parent::__construct($message, $code, $previous);
  }

}
