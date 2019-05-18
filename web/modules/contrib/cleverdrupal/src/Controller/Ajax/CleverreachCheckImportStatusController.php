<?php

namespace Drupal\cleverreach\Controller\Ajax;

use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Queue;
use CleverReach\Infrastructure\TaskExecution\QueueItem;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * CheckStatus endpoint.
 */
class CleverreachCheckImportStatusController {
  /**
   * @var \CleverReach\Infrastructure\TaskExecution\Queue
   */
  private $queue;
  /**
   * @var \CleverReach\Infrastructure\Interfaces\Required\Configuration
   */
  private $configService;

  /**
   * Return an array to be run through json_encode and sent to the client.
   */
  public function render() {
    /** @var \CleverReach\Infrastructure\TaskExecution\QueueItem $syncTaskQueueItem */
    $syncTaskQueueItem = $this->getQueueService()->findLatestByType('InitialSyncTask');

    if (NULL === $syncTaskQueueItem) {
      return new JsonResponse(['status' => QueueItem::FAILED]);
    }

    /** @var \CleverReach\BusinessLogic\Sync\InitialSyncTask $syncTask */
    $syncTask = $syncTaskQueueItem->getTask();
    $syncProgress = $syncTask->getProgressByTask();

    return new JsonResponse(
        [
          'status' => $syncTaskQueueItem->getStatus(),
          'statistics' => [
            'recipients_count' => $syncTask->getSyncedRecipientsCount(),
            'group_name' => $this->getConfigService()->getIntegrationName(),
          ],
          'taskStatuses' => [
            'subscriber_list' => [
              'status' => $this->getStatus($syncProgress['subscriberList']),
              'progress' => $syncProgress['subscriberList'],
            ],
            'add_fields' => [
              'status' => $this->getStatus($syncProgress['fields']),
              'progress' => $syncProgress['fields'],
            ],
            'recipient_sync' => [
              'status' => $this->getStatus($syncProgress['recipients']),
              'progress' => $syncProgress['recipients'],
            ],
          ],
          'syncProgress' => $syncProgress,
        ]
    );
  }

  /**
   * Get current progress of initial sync.
   *
   * @param int $progress
   *
   * @return string
   */
  private function getStatus($progress) {
    $status = QueueItem::QUEUED;
    if (0 < $progress && $progress < 100) {
      $status = QueueItem::IN_PROGRESS;
    }
    elseif ($progress >= 100) {
      $status = QueueItem::COMPLETED;
    }
    return $status;
  }

  /**
   * Gets CleverReach configuration service.
   *
   * @return \CleverReach\Infrastructure\Interfaces\Required\Configuration
   */
  private function getConfigService() {
    if (NULL === $this->configService) {
      $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
    }
    return $this->configService;
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
