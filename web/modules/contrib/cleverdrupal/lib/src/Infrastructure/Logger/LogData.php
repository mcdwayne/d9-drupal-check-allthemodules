<?php

namespace CleverReach\Infrastructure\Logger;

/**
 *
 */
class LogData {

  /**
   * @var string
   */
  private $integration;

  /**
   * @var string
   */
  private $userAccount;

  /**
   * @var int
   */
  private $logLevel;

  /**
   * @var int
   */
  private $timestamp;

  /**
   * @var string
   */
  private $component;

  /**
   * @var string
   */
  private $message;

  /**
   *
   */
  public function __construct($integration, $userAccount, $logLevel, $timestamp, $component, $message) {
    $this->integration = $integration;
    $this->userAccount = $userAccount;
    $this->logLevel = $logLevel;
    $this->component = $component;
    $this->timestamp = $timestamp;
    $this->message = $message;
  }

  /**
   * @return string
   */
  public function getIntegration() {
    return $this->integration;
  }

  /**
   * @return string
   */
  public function getUserAccount() {
    return $this->userAccount;
  }

  /**
   * @return int
   */
  public function getLogLevel() {
    return $this->logLevel;
  }

  /**
   * @return int
   */
  public function getTimestamp() {
    return $this->timestamp;
  }

  /**
   * @return string
   */
  public function getComponent() {
    return $this->component;
  }

  /**
   * @return string
   */
  public function getMessage() {
    return $this->message;
  }

}
