<?php

namespace CleverReach\Infrastructure\Logger;

use CleverReach\Infrastructure\Interfaces\DefaultLoggerAdapter;
use CleverReach\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use CleverReach\Infrastructure\ServiceRegister;

/**
 *
 */
class Logger {

  const ERROR = 0;
  const WARNING = 1;
  const INFO = 2;
  const DEBUG = 3;

  private static $instance;

  /**
   * Shop logger.
   *
   * @var \CleverReach\Infrastructure\Interfaces\Required\ShopLoggerAdapter
   */
  private $shopLogger;

  /**
   * Default logger.
   *
   * @var DefaultLogger
   */
  private $defaultLogger;

  /**
   * Getting logger component instance.
   *
   * @return Logger
   */
  public static function getInstance() {
    if (empty(self::$instance)) {
      self::$instance = new Logger();
    }

    return self::$instance;
  }

  /**
   *
   */
  public function __construct() {
    $this->defaultLogger = ServiceRegister::getService(DefaultLoggerAdapter::CLASS_NAME);
    $this->shopLogger = ServiceRegister::getService(ShopLoggerAdapter::CLASS_NAME);

    self::$instance = $this;
  }

  /**
   * Logging error message.
   *
   * @param string $message
   * @param string $component
   */
  public static function logError($message, $component = 'Core') {
    self::getInstance()->logMessage(self::ERROR, $message, $component);
  }

  /**
   * Logging warning message.
   *
   * @param string $message
   * @param string $component
   */
  public static function logWarning($message, $component = 'Core') {
    self::getInstance()->logMessage(self::WARNING, $message, $component);
  }

  /**
   * Logging info message.
   *
   * @param string $message
   * @param string $component
   */
  public static function logInfo($message, $component = 'Core') {
    self::getInstance()->logMessage(self::INFO, $message, $component);
  }

  /**
   * Logging debug message.
   *
   * @param string $message
   * @param string $component
   */
  public static function logDebug($message, $component = 'Core') {
    self::getInstance()->logMessage(self::DEBUG, $message, $component);
  }

  /**
   * Logging message.
   *
   * @param int $level
   * @param string $message
   * @param string $component
   */
  private function logMessage($level, $message, $component) {
    $config = Configuration::getInstance();
    $logData = new LogData(
        $config->getIntegrationName(),
        $config->getUserAccountId(),
        $level,
        date('Y-m-d H:i:s'),
        $component,
        $message
    );

    // If default logger is turned on and message level is lower or equal than set in configuration.
    if ($config->isDefaultLoggerEnabled() && $level <= $config->getMinLogLevel()) {
      $this->defaultLogger->logMessage($logData);
    }

    $this->shopLogger->logMessage($logData);
  }

}
