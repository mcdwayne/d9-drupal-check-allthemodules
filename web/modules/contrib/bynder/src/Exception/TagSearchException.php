<?php

namespace Drupal\bynder\Exception;

use Drupal\Core\Url;

/**
 * Exception indicating there was an error fetching tags from the Tag Search Service.
 */
class TagSearchException extends BynderException {

  /**
   * Constructs TagSearchException.
   */
  public function __construct($original_message) {
    $log_message = 'Unable to retrieve tags: @message';
    $log_message_args = ['@message' => $original_message];
    $admin_message = $this->t($log_message, $log_message_args);
    $message = $this->t(
      'Searching for tags failed. Please see the logs for more information.'
    );
    parent::__construct(
      $message,
      $admin_message,
      $log_message,
      $log_message_args
    );
  }

}
