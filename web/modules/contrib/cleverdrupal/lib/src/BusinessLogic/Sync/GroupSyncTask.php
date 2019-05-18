<?php

namespace CleverReach\BusinessLogic\Sync;

use CleverReach\Infrastructure\Exceptions\InvalidConfigurationException;

/**
 *
 */
class GroupSyncTask extends BaseSyncTask {

  /**
   * Runs task logic.
   *
   * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
   */
  public function execute() {
    /** @var string $serviceName */
    $serviceName = $this->getConfigService()->getIntegrationListName();

    $this->reportAlive();
    $this->validateServiceName($serviceName);
    $this->reportProgress(50);

    $groupId = $this->getProxy()->getGroupId($serviceName);

    if ($groupId === NULL) {
      $this->reportProgress(75);
      $newGroupId = $this->getProxy()->createGroup($serviceName);
      $this->getConfigService()->setIntegrationId($newGroupId);
    }
    else {
      $this->getConfigService()->setIntegrationId($groupId);
    }

    $this->reportProgress(100);
  }

  /**
   * Validates if $serviceName parameter is set.
   *
   * @param $serviceName
   *
   * @throws InvalidConfigurationException
   */
  private function validateServiceName($serviceName) {
    if (empty($serviceName)) {
      throw new InvalidConfigurationException('Integration name not set in Configuration Service');
    }
  }

}
