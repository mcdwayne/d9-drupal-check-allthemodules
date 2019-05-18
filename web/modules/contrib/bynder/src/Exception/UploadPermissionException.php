<?php

namespace Drupal\bynder\Exception;

/**
 * Exception indicating that the User doesn't have upload permissions.
 */
class UploadPermissionException extends BynderException {

  /**
   * Constructs UploadFailedException.
   *
   * @param string $original_message
   *   Message that was originally thrown from the upload system.
   */
  public function __construct($original_message) {
    $log_message = "Unable to upload files to Bynder. Make sure your user account has enough permissions : @message";
    $log_message_args = ['@message' => $original_message];
    $admin_message = $this->t($log_message, $log_message_args);
    $message = $this->t(
      "Unable to upload files to Bynder. Make sure your user account has enough permissions."
    );
    parent::__construct(
      $message,
      $admin_message,
      $log_message,
      $log_message_args
    );
  }

}
