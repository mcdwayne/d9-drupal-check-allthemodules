<?php

namespace Drupal\hold_my_draft;

use Drupal\Core\Logger\LoggerChannelFactory;

/**
 * Class Logger. Provides logging for the hold my draft module.
 *
 * @package Drupal\hold_my_draft
 */
class Logger {

  /**
   * The logger factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * Logger constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactory $factory
   *   The logger factory service.
   */
  public function __construct(LoggerChannelFactory $factory) {
    $this->loggerFactory = $factory;
  }

  /**
   * Throw a hold my draft error.
   *
   * @param string $message
   *   Expects a string message.
   */
  public function setError(string $message) {
    $this->loggerFactory->get('hold_my_draft')->error($message);
  }

  /**
   * Set a hold my draft notice.
   *
   * @param string $message
   *   Expects a message string.
   */
  public function setNotice(string $message) {
    $this->loggerFactory->get('hold_my_draft')->notice($message);
  }

}
