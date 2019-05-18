<?php

namespace Drupal\bynder\Exception;

/**
 * Exception indicating that the usage can't be added for the Bynder asset.
 */
class UnableToAddUsageException extends BynderException {

  /**
   * Constructs UnableToAddUsageException.
   */
  public function __construct($original_message) {
    $log_message = 'Unable to add usage for bynder asset: @message';
    $log_message_args = ['@message' => $original_message];
    $message = $this->t(
      'Unable to add usage for Bynder asset. Please see the logs for more information.'
    );

    parent::__construct(
      $message,
      NULL,
      $log_message,
      $log_message_args
    );
  }

}
