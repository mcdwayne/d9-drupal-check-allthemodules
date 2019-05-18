<?php

namespace Drupal\cleverreach\Controller\Ajax;

use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Queue;
use CleverReach\Infrastructure\TaskExecution\QueueItem;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * CheckStatus endpoint.
 */
class CleverreachCheckAuthStatusController {
  /**
   * @var \CleverReach\Infrastructure\TaskExecution\Queue
   */
  private $queue;

  /**
   * Return an array to be run through json_encode and sent to the client.
   */
  public function render() {
    $status = 'finished';

    /** @var \CleverReach\Infrastructure\TaskExecution\QueueItem $queueItem */
    $queueItem = $this->getQueueService()->findLatestByType('RefreshUserInfoTask');
    if (NULL !== $queueItem) {
      $queueStatus = $queueItem->getStatus();
      if ($queueStatus !== QueueItem::FAILED && $queueStatus !== QueueItem::COMPLETED) {
        $status = QueueItem::IN_PROGRESS;
      }
    }

    return new JsonResponse(['status' => $status]);
  }

  /**
   * Gets CleverReach queue service.
   *
   * @return \CleverReach\Infrastructure\TaskExecution\Queue
   */
  private function getQueueService() {
    if (NULL === $this->queue) {
      $this->queue = ServiceRegister::getService(Queue::CLASS_NAME);
    }
    return $this->queue;
  }

}
