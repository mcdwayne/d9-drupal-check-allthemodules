<?php

namespace Drupal\cleverreach\Controller\Ajax;

use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\Infrastructure\ServiceRegister;
use Drupal\cleverreach\Component\Utility\TaskQueue;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Configuration endpoint.
 */
class CleverreachConfigController {
  /**
   * @var \Drupal\cleverreach\Component\Infrastructure\ConfigService
   */
  private $configService;

  /**
   * Return an array to be run through json_encode and sent to the client.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function execute(Request $request) {
    TaskQueue::wakeup();

    $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);

    if ($request->isMethod('POST') || $request->isMethod('PUT')) {
      return $this->updateConfigParameters($request);
    }

    return $this->getConfigParameters();
  }

  /**
   * Returns all configuration parameters for diagnostics purposes.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  private function getConfigParameters() {
    $queue = ServiceRegister::getService(TaskQueueStorage::CLASS_NAME);

    $items = [];
    /** @var \CleverReach\Infrastructure\TaskExecution\QueueItem $item */
    foreach ($queue->findAll() as $item) {
      $items[] = [
        'type' => $item->getTaskType(),
        'status' => $item->getStatus(),
        'startedAt' => date('c', $item->getStartTimestamp()),
        'progress' => $item->getProgressFormatted(),
        'retries' => $item->getRetries(),
        'failure' => $item->getFailureDescription(),
      ];
    }

    $config = [
      'integrationId' => $this->configService->getIntegrationId(),
      'integrationName' => $this->configService->getIntegrationName(),
      'minLogLevel' => $this->configService->getMinLogLevel(),
      'isProductSearchEnabled' => $this->configService->isProductSearchEnabled(),
      'productSearchParameters' => $this->configService->getProductSearchParameters(),
      'recipientsSynchronizationBatchSize' => $this->configService->getRecipientsSynchronizationBatchSize(),
      'isDefaultLoggerEnabled' => $this->configService->isDefaultLoggerEnabled(),
      'maxStartedTasksLimit' => $this->configService->getMaxStartedTasksLimit(),
      'maxTaskExecutionRetries' => $this->configService->getMaxTaskExecutionRetries(),
      'maxTaskInactivityPeriod' => $this->configService->getMaxTaskInactivityPeriod(),
      'taskRunnerMaxAliveTime' => $this->configService->getTaskRunnerMaxAliveTime(),
      'taskRunnerStatus' => $this->configService->getTaskRunnerStatus(),
      'taskRunnerWakeupDelay' => $this->configService->getTaskRunnerWakeupDelay(),
      'defaultRecipientStatus' => $this->configService->getDefaultRecipientStatus(),
      'configuredInitialSync' => $this->configService->isConfiguredInitialSync(),
      'queueName' => $this->configService->getQueueName(),
      'drupalVersion' => \Drupal::VERSION,
      'currentQueue' => $items,
    ];

    return new JsonResponse(
        [
          'status' => 'success',
          'config' => $config,
        ]
    );
  }

  /**
   * Updates configuration from POST request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  private function updateConfigParameters(Request $request) {
    $payload = json_decode($request->getContent(), TRUE);

    if (array_key_exists('minLogLevel', $payload)) {
      $this->configService->saveMinLogLevel($payload['minLogLevel']);
    }
    if (array_key_exists('defaultLoggerStatus', $payload)) {
      $this->configService->setDefaultLoggerEnabled($payload['defaultLoggerStatus']);
    }
    if (array_key_exists('maxStartedTasksLimit', $payload)) {
      $this->configService->setMaxStartedTaskLimit($payload['maxStartedTasksLimit']);
    }
    if (array_key_exists('taskRunnerWakeUpDelay', $payload)) {
      $this->configService->setTaskRunnerWakeUpDelay($payload['taskRunnerWakeUpDelay']);
    }
    if (array_key_exists('taskRunnerMaxAliveTime', $payload)) {
      $this->configService->setTaskRunnerMaxAliveTime($payload['taskRunnerMaxAliveTime']);
    }
    if (array_key_exists('maxTaskExecutionRetries', $payload)) {
      $this->configService->setMaxTaskExecutionRetries($payload['maxTaskExecutionRetries']);
    }
    if (array_key_exists('maxTaskInactivityPeriod', $payload)) {
      $this->configService->setMaxTaskInactivityPeriod($payload['maxTaskInactivityPeriod']);
    }
    if (array_key_exists('defaultRecipientStatus', $payload)) {
      $this->configService->setDefaultRecipientStatus($payload['defaultRecipientStatus']);
    }
    if (array_key_exists('configuredInitialSync', $payload)) {
      $this->configService->setConfiguredInitialSync($payload['configuredInitialSync']);
    }

    return $this->getConfigParameters();
  }

}
