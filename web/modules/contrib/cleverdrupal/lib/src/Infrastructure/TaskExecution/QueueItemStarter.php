<?php

namespace CleverReach\Infrastructure\TaskExecution;

use CleverReach\Infrastructure\Interfaces\Exposed\Runnable;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;

/**
 *
 */
class QueueItemStarter implements Runnable {

  /**
   * @var intIdofqueueitemtostart*/
  private $queueItemId;

  /**
   * @var Queue*/
  private $queue;

  /**
   * @var \CleverReach\Infrastructure\Interfaces\Required\Configuration*/
  private $configService;

  /**
   *
   */
  public function __construct($queueItemId) {
    $this->queueItemId = $queueItemId;
  }

  /**
   * String representation of object.
   * @link http://php.net/manual/en/serializable.serialize.php
   *
   * @return string the string representation of the object or null
   *
   * @since 5.1.0
   */
  public function serialize() {
    return serialize([$this->queueItemId]);
  }

  /**
   * Constructs the object.
   * @link http://php.net/manual/en/serializable.unserialize.php
   *
   * @param string $serialized
   *   <p>
   *   The string representation of the object.
   *   </p>.
   *
   * @return void
   *
   * @since 5.1.0
   */
  public function unserialize($serialized) {
    list($this->queueItemId) = unserialize($serialized);
  }

  /**
   * @inheritdoc
   */
  public function run() {
    $queueItem = $this->fetchItem();

    if (empty($queueItem) || ($queueItem->getStatus() !== QueueItem::QUEUED)) {
      Logger::logDebug(
        json_encode([
          'Message' => 'Fail to start task execution because task no longer exists or it is not in queued state anymore.',
          'TaskId' => $this->getQueueItemId(),
          'Status' => !empty($queueItem) ? $queueItem->getStatus() : 'unknown',
        ])
      );
      return;
    }

    try {
      $this->getConfigService()->setContext($queueItem->getContext());
      $this->getQueueService()->start($queueItem);
      $this->getQueueService()->finish($queueItem);
    }
    catch (\Exception $ex) {
      if (QueueItem::IN_PROGRESS === $queueItem->getStatus()) {
        $this->getQueueService()->fail($queueItem, $ex->getMessage());
      }

      Logger::logError(
        json_encode([
          'Message' => 'Fail to start task execution.',
          'TaskId' => $this->getQueueItemId(),
          'ExceptionMessage' => $ex->getMessage(),
        ])
        );
      Logger::logDebug(
            json_encode([
              'Message' => 'Fail to start task execution.',
              'TaskId' => $this->getQueueItemId(),
              'ExceptionMessage' => $ex->getMessage(),
              'ExceptionTrace' => $ex->getTraceAsString(),
            ])
        );
    }
  }

  /**
   * Gets id of a queue item that will be run.
   *
   * @return int
   */
  public function getQueueItemId() {
    return $this->queueItemId;
  }

  /**
   * @return QueueItem|null
   */
  private function fetchItem() {
    $queueItem = NULL;

    try {
      $queueItem = $this->getQueueService()->find($this->queueItemId);
    }
    catch (\Exception $ex) {
      Logger::logError(json_encode([
        'Message' => 'Fail to start task execution.',
        'TaskId' => $this->getQueueItemId(),
        'ExceptionMessage' => $ex->getMessage(),
      ]));
      Logger::logDebug(json_encode([
        'Message' => 'Fail to start task execution.',
        'TaskId' => $this->getQueueItemId(),
        'ExceptionMessage' => $ex->getMessage(),
        'ExceptionTrace' => $ex->getTraceAsString(),
      ]));
    }

    return $queueItem;
  }

  /**
   *
   */
  private function getQueueService() {
    if (empty($this->queue)) {
      $this->queue = ServiceRegister::getService(Queue::CLASS_NAME);
    }

    return $this->queue;
  }

  /**
   *
   */
  private function getConfigService() {
    if (empty($this->configService)) {
      $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    return $this->configService;
  }

}
