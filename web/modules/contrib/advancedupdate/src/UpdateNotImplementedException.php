<?php

namespace Drupal\advanced_update;

use Exception;

/**
 * Class UpdateNotImplementedException.
 *
 * @package Drupal\advanced_update
 */
class UpdateNotImplementedException extends \Exception {

  /**
   * {@inheritdoc}
   */
  public function __construct($message = "", $code = 0, Exception $previous = NULL) {
    if (empty($message)) {
      $message = (string) \Drupal::translation()
        ->translate('Method not implemented');
    }
    parent::__construct($message, $code, $previous);
  }

}
