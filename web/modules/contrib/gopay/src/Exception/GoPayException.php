<?php

namespace Drupal\gopay\Exception;

/**
 * Class GoPayException.
 *
 * @package Drupal\gopay\Exception
 */
class GoPayException extends \Exception {

  /**
   * {@inheritdoc}
   */
  public function __construct($message = "", $code = 0, \Throwable $previous = NULL) {
    parent::__construct('GoPay:' . $message, $code, $previous);
  }

}
