<?php

namespace CleverReach\Infrastructure\TaskExecution;

use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\TaskEvents\AliveAnnouncedTaskEvent;
use CleverReach\Infrastructure\TaskExecution\TaskEvents\ProgressedTaskEvent;
use CleverReach\Infrastructure\Utility\Events\EventEmitter;
use CleverReach\Infrastructure\Utility\TimeProvider;

/**
 *
 */
abstract class Task extends EventEmitter implements \Serializable {
  /**
 * Max inactivity period for a task in seconds .*/
  const MAX_INACTIVITY_PERIOD = 300;
  /**
 * Minimal number of seconds that must pass between two alive signals .*/
  const ALIVE_SIGNAL_FREQUENCY = 2;
  /**
   * @var \DateTime*/
  private $lastAliveSignalTime;
  /**
   * @var \CleverReach\Infrastructure\Interfaces\Required\Configuration*/
  private $configService;

  /**
   * Runs task logic.
   */
  abstract public function execute();

  /**
   * @inheritdoc
   */
  public function serialize() {
    return serialize($this);
  }

  /**
   * @inheritdoc
   */
  public function unserialize($serialized) {
    // This method was intentionally left blank because this task doesn't have any properties which needs to encapsulate.
  }

  /**
   * Reports task progress by emitting ProgressedTaskEvent and defers next AliveAnnouncedTaskEvent.
   *
   * @param float|int $progressPercent
   *   Float representation of progress percentage, value between 0 and
   *   100 that immediately will be converted to base points. One base point is equal to 0.01%. For example 23.58% is
   *   equal to 2358 base points.
   *
   * @throws \InvalidArgumentException In case when progress percent is outside of 0 - 100 boundaries or not an float
   */
  public function reportProgress($progressPercent) {
    if (!is_int($progressPercent) && !is_float($progressPercent)) {
      throw new \InvalidArgumentException('Progress percentage must be value integer or float value');
    }

    /** @var \CleverReach\Infrastructure\Utility\TimeProvider $timeProvider */
    $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
    $this->lastAliveSignalTime = $timeProvider->getCurrentLocalTime();

    $this->fire(new ProgressedTaskEvent($this->percentToBasePoints($progressPercent)));
  }

  /**
   * Reports that task is alive by emitting AliveAnnouncedTaskEvent.
   */
  public function reportAlive() {
    /** @var \CleverReach\Infrastructure\Utility\TimeProvider $timeProvider */
    $timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
    $currentTime = $timeProvider->getCurrentLocalTime();
    if ($this->lastAliveSignalTime === NULL
        || $this->lastAliveSignalTime->getTimestamp() + self::ALIVE_SIGNAL_FREQUENCY < $currentTime->getTimestamp()
    ) {
      $this->lastAliveSignalTime = $currentTime;
      $this->fire(new AliveAnnouncedTaskEvent());
    }
  }

  /**
   * Gets max inactivity period for a task.
   *
   * @return int Max inactivity period for a task in seconds
   */
  public function getMaxInactivityPeriod() {
    $configurationValue = $this->getConfigService()->getMaxTaskInactivityPeriod();

    return $configurationValue !== NULL ? $configurationValue : static::MAX_INACTIVITY_PERIOD;
  }

  /**
   * Gets name of the class.
   * Alias method for static method {@see self::getClassName()}.
   *
   * @return string
   */
  public function getType() {
    return static::getClassName();
  }

  /**
   * Gets name of the class.
   *
   * @return string
   */
  public static function getClassName() {
    $namespaceParts = explode('\\', get_called_class());
    $name = end($namespaceParts);

    if ($name === 'Task') {
      throw new \RuntimeException('Constant CLASS_NAME not defined in class ' . get_called_class());
    }

    return $name;
  }

  /**
   * Determines whether task can be reconfigured.
   *
   * @return bool
   */
  public function canBeReconfigured() {
    return FALSE;
  }

  /**
   * Reconfigures the task.
   */
  public function reconfigure() {

  }

  /**
   * Calculates base points for progress tracking from percent value.
   *
   * @param float $percentValue
   *
   * @return int
   */
  private function percentToBasePoints($percentValue) {
    return (int) round($percentValue * 100, 2);
  }

  /**
   * Gets Configuration service.
   *
   * @return \CleverReach\Infrastructure\Interfaces\Required\Configuration
   */
  protected function getConfigService() {
    if ($this->configService === NULL) {
      $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    return $this->configService;
  }

}
