<?php

/**
 * @file
 * Contains \Drupal\cas_server\Logger\DebugLogger.
 */

namespace Drupal\cas_server\Logger;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Class DebugLogger;
 */
class DebugLogger {

  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * Stores logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $loggerChannel;

  /**
   * Constructor.
   *
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param LoggerChannelFactory $logger_factory
   *   The logger channel factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactory $logger_factory) {
    $this->settings = $config_factory->get('cas_server.settings');
    $this->loggerChannel = $logger_factory->get('cas_server');
  }

  /**
   * Log information to the logger.
   *
   * Only log supplied information if module is configured to do so, otherwise
   * do nothing.
   *
   * @param string $message
   *   The message to log.
   */
  public function log($message) {
    if ($this->settings->get('debugging.log') == TRUE) {
      $this->loggerChannel->log(RfcLogLevel::DEBUG, $message);
    }
  }
}
