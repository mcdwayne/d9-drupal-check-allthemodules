<?php

namespace CleverReach\Infrastructure\Logger;

use CleverReach\Infrastructure\Interfaces\Required\Configuration as ConfigInterface;
use CleverReach\Infrastructure\ServiceRegister;

/**
 *
 */
class Configuration {

  const DEFAULT_MIN_LOG_LEVEL = Logger::DEBUG;
  const DEFAULT_IS_DEFAULT_LOGGER_ENABLED = FALSE;

  const BASE_LOGGER_URL = '';

  private static $instance;

  /**
   * @var bool
   */
  private $isDefaultLoggerEnabled;

  /**
   * @var \CleverReach\Infrastructure\Interfaces\Required\Configuration
   */
  private $shopConfiguration;

  /**
   * @var int
   */
  private $minLogLevel;

  /**
   * @var string
   */
  private $integrationName;

  /**
   * @var string
   */
  private $userAccountId;

  /**
   * Getting logger configuration instance.
   *
   * @return Configuration
   */
  public static function getInstance() {
    if (empty(self::$instance)) {
      self::$instance = new Configuration();
    }

    return self::$instance;
  }

  /**
   * Resetting singleton instance. Required for proper tests.
   */
  public static function resetInstance() {
    self::$instance = NULL;
  }

  /**
   * @return bool
   */
  public function isDefaultLoggerEnabled() {
    if (empty($this->isDefaultLoggerEnabled)) {
      try {
        $this->isDefaultLoggerEnabled = $this->getShopConfiguration()->isDefaultLoggerEnabled();
      }
      catch (\Exception $ex) {
        // Catch if configuration is not set properly and for some reason throws exception
        // e.g. Client is still not authorized (meaning that configuration is not set) and we want to log something.
      }
    }

    return !empty($this->isDefaultLoggerEnabled) ? $this->isDefaultLoggerEnabled : self::DEFAULT_IS_DEFAULT_LOGGER_ENABLED;
  }

  /**
   * @param bool $loggerStatus
   */
  public function setIsDefaultLoggerEnabled($loggerStatus) {
    $this->getShopConfiguration()->setDefaultLoggerEnabled($loggerStatus);
    $this->isDefaultLoggerEnabled = $loggerStatus;
  }

  /**
   * @return int
   */
  public function getMinLogLevel() {
    if (!isset($this->minLogLevel)) {
      try {
        $this->minLogLevel = $this->getShopConfiguration()->getMinLogLevel();
      }
      catch (\Exception $ex) {
        // Catch if configuration is not set properly and for some reason throws exception
        // e.g. Client is still not authorized (meaning that configuration is not set) and we want to log something.
      }
    }

    return isset($this->minLogLevel) ? $this->minLogLevel : self::DEFAULT_MIN_LOG_LEVEL;
  }

  /**
   * @param int $minLogLevel
   */
  public function setMinLogLevel($minLogLevel) {
    $this->getShopConfiguration()->saveMinLogLevel($minLogLevel);
    $this->minLogLevel = $minLogLevel;
  }

  /**
   * @return string
   */
  public function getIntegrationName() {
    if (empty($this->integrationName)) {
      try {
        $this->integrationName = $this->getShopConfiguration()->getIntegrationName();
      }
      catch (\Exception $ex) {
        // Catch if configuration is not set properly and for some reason throws exception
        // e.g. Client is still not authorized (meaning that configuration is not set) and we want to log something.
      }
    }

    return !empty($this->integrationName) ? $this->integrationName : 'unknown';
  }

  /**
   * @return string
   */
  public function getUserAccountId() {
    if (empty($this->userAccountId)) {
      try {
        $this->userAccountId = $this->getShopConfiguration()->getUserAccountId();
      }
      catch (\Exception $ex) {
        // Catch if configuration is not set properly and for some reason throws exception
        // e.g. Client is still not authorized (meaning that configuration is not set) and we want to log something.
      }
    }

    return !empty($this->userAccountId) ? $this->userAccountId : '';
  }

  /**
   * Set default logger status (turning on/off)
   *
   * @param bool $status
   */
  public static function setDefaultLoggerEnabled($status) {
    self::getInstance()->setIsDefaultLoggerEnabled($status);
  }

  /**
   *
   */
  private function getShopConfiguration() {
    if (empty($this->shopConfiguration)) {
      $this->shopConfiguration = ServiceRegister::getService(ConfigInterface::CLASS_NAME);
    }

    return $this->shopConfiguration;
  }

}
