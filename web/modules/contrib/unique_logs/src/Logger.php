<?php

namespace Drupal\unique_logs;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

class Logger {

  /**
   * Logger Channel Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  private $loggerFactory;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface[]
   */
  static $channels = [];

  /**
   * Static array of all the logs already logged once.
   *
   * @var array
   */
  static $logs = [];

  /**
   * Logger constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger Channel Factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Wrapper function to log message only once per request.
   *
   * @param string $type
   *   Log type (error/warning/info/etc.).
   * @param string $channel
   *   Logger channel to use for logging the message. Mostly module name.
   * @param string $message
   *   Actual message to log.
   * @param array $context
   *   Context information for the message.
   */
  public function log(string $type, string $channel, string $message, array $context = []) {
    $full_message = serialize([
      'type' => $type,
      'channel' => $channel,
      'message' => $message,
      '$context' => $context,
    ]);

    if (isset(self::$logs[$full_message])) {
      return;
    }

    $logger = $this->getChannel($channel);
    $logger->$type($message, $context);
    self::$logs[$full_message] = 1;
  }

  /**
   * @param string $channel
   *
   * @return \Drupal\Core\Logger\LoggerChannelInterface
   */
  private function getChannel(string $channel): LoggerChannelInterface {
    if (!isset(self::$channels[$channel])) {
      self::$channels[$channel] = $this->loggerFactory->get($channel);
    }

    return self::$channels[$channel];
  }

}
