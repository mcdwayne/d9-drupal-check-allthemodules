<?php

namespace CleverReach\Infrastructure\TaskExecution;

use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\Utility\TimeProvider;

/**
 *
 */
class TaskRunnerStatus {
  /**
 * Maximal time allowed for runner instance to stay in alive (running) status in seconds .*/
  const MAX_ALIVE_TIME = 60;

  /**
   * @var string*/
  private $guid;

  /**
   * @var int|null*/
  private $aliveSinceTimestamp;

  /**
   * @var \CleverReach\Infrastructure\Utility\TimeProvider*/
  private $timeProvider;

  /**
   * @var \CleverReach\Infrastructure\Interfaces\Required\Configuration*/
  private $configService;

  /**
   * TaskRunnerStatus constructor.
   *
   * @param string $guid
   *   Runner instance identifier.
   * @param int|null $aliveSinceTimestamp
   */
  public function __construct($guid, $aliveSinceTimestamp) {
    $this->guid = $guid;
    $this->aliveSinceTimestamp = $aliveSinceTimestamp;
    $this->timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
    $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
  }

  /**
   *
   */
  public static function createNullStatus() {
    return new self('', NULL);
  }

  /**
   * Gets runner instance identifier.
   *
   * @return string
   */
  public function getGuid() {
    return $this->guid;
  }

  /**
   * Gets timestamp since runner is in alive status or null if runner was never alive.
   *
   * @return int|null
   */
  public function getAliveSinceTimestamp() {
    return $this->aliveSinceTimestamp;
  }

  /**
   *
   */
  public function isExpired() {
    $currentTimestamp = $this->timeProvider->getCurrentLocalTime()->getTimestamp();
    return !empty($this->aliveSinceTimestamp) && ($this->aliveSinceTimestamp + $this->getMaxAliveTimestamp() < $currentTimestamp);
  }

  /**
   *
   */
  private function getMaxAliveTimestamp() {
    $configurationValue = $this->configService->getTaskRunnerMaxAliveTime();
    return !is_null($configurationValue) ? $configurationValue : self::MAX_ALIVE_TIME;
  }

}
