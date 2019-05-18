<?php

namespace Drupal\bynder\Exception;

use Drupal\Core\Url;

/**
 * Exception indicating that the selected bundle does not exist.
 */
class UnableToConnectException extends BynderException {

  /**
   * Constructs UnableToConnectException.
   */
  public function __construct() {
    $log_message = 'Unable to connect to Bynder. Check if the  <a target="_blank" href=":url">configuration is set properly</a> or contact <a href=":support">support</a>.';
    $log_message_args = [
      ':url' => Url::fromRoute('bynder.configuration_form')->toString(),
      ':support' => 'mailto:support@getbynder.com',
    ];
    $admin_message = $this->t($log_message, $log_message_args);
    $message = $this->t(
      'Unable to connect to Bynder. Please contact the site administrator.'
    );
    parent::__construct(
      $message,
      $admin_message,
      $log_message,
      $log_message_args
    );
  }

}
