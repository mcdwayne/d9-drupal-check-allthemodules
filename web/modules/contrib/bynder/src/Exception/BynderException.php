<?php

namespace Drupal\bynder\Exception;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base exception class for Bynder.
 */
abstract class BynderException extends \Exception {

  use StringTranslationTrait;


  /**
   * Admin permission related to this exception.
   *
   * @var string
   */
  protected $adminPermission = 'administer bynder configuration';

  /**
   * Message level to be used when displaying the message to the user.
   *
   * @var string
   */
  protected $messageLevel = 'error';

  /**
   * User-facing for admin users.
   *
   * @var string
   */
  protected $adminMessage;

  /**
   * Message to be logged in the Drupal's log.
   *
   * @var string
   */
  protected $logMessage;

  /**
   * Arguments for the log message.
   *
   * @var array
   */
  protected $logMessageArgs;

  /**
   * Constructs BundleNotExistException.
   */
  public function __construct(
    $message,
    $admin_message = NULL,
    $log_message = NULL,
    $log_message_args = []
  ) {
    $this->adminMessage = $admin_message ?: $message;
    $this->logMessage = $log_message ?: $this->adminMessage;
    $this->logMessageArgs = $log_message_args;
    parent::__construct($message);
  }

  /**
   * Displays message to the user.
   */
  public function displayMessage() {
    if (\Drupal::currentUser()->hasPermission($this->adminPermission)) {
      drupal_set_message($this->adminMessage, $this->messageLevel);
    }
    else {
      drupal_set_message($this->getMessage(), $this->messageLevel);
    }
  }

  /**
   * Logs exception into Drupal's log.
   *
   * @return \Drupal\bynder\Exception\BynderException
   *   This exception.
   */
  public function logException() {
    \Drupal::logger('bynder')->error($this->logMessage, $this->logMessageArgs);
    return $this;
  }

}
