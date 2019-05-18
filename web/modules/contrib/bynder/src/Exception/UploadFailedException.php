<?php

namespace Drupal\bynder\Exception;

/**
 * Exception indicating that the upload to Bynder failed.
 */
class UploadFailedException extends BynderException {

  /**
   * Constructs UploadFailedException.
   *
   * @param string $original_message
   *   Message that was originally thrown from the upload system.
   */
  public function __construct($original_message) {
    $log_message = 'Unable to upload files to Bynder: @message';
    $log_message_args = ['@message' => $original_message];
    $admin_message = $this->t($log_message, $log_message_args);
    $message = $this->t(
      'Upload to Bynder failed. Please contact the site administrator.'
    );
    parent::__construct(
      $message,
      $admin_message,
      $log_message,
      $log_message_args
    );
  }

}
