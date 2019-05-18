<?php

namespace Drupal\bynder\Exception;

/**
 * Exception indicating that the usage can't be deleted for the Bynder asset.
 */
class UnableToDeleteUsageException extends BynderException {

  /**
   * Constructs UnableToDeleteUsageException.
   */
  public function __construct($original_message) {
    $log_message = 'Unable to delete usage: @message';
    $log_message_args = ['@message' => $original_message];
    $message = $this->t(
      'Unable to delete usage from Bynder asset. Please see the logs for more information.'
    );

    parent::__construct(
      $message,
      NULL,
      $log_message,
      $log_message_args
    );
  }
}
