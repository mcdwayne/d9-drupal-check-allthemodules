<?php

namespace CleverReach\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Interfaces\Recipients;
use CleverReach\BusinessLogic\Utility\Filter;
use CleverReach\Infrastructure\Exceptions\InvalidConfigurationException;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\BusinessLogic\Utility\Rule;

/**
 *
 */
class FilterSyncTask extends BaseSyncTask {
  const INITIAL_PROGRESS_PERCENT = 10;
  /**
   * @var \CleverReach\BusinessLogic\Interfaces\Recipients
   */
  private $recipientsService;
  /**
   * @var int
   */
  private $integrationId;
  /**
   * @var int
   */
  private $progressPercent = self::INITIAL_PROGRESS_PERCENT;
  /**
   * @var int
   */
  private $progressStep;

  /**
   * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
   */
  public function execute() {
    $this->integrationId = $this->getConfigService()->getIntegrationId();

    if ($this->integrationId === NULL) {
      throw new InvalidConfigurationException('Integration ID not set');
    }

    /** @var \CleverReach\BusinessLogic\Entity\TagCollection $shopTags */
    $shopTags = $this->getRecipientsService()->getAllTags();

    $this->reportAlive();

    /** @var \CleverReach\BusinessLogic\Utility\Filter[] $allCRFilters */
    $allCRFilters = $this->getProxy()->getAllFilters($this->integrationId);

    $totalNumberOfFilters = count($shopTags) + count($allCRFilters);

    if ($totalNumberOfFilters === 0) {
      $this->reportProgress(100);

      return;
    }

    $this->reportProgress($this->progressPercent);

    $this->progressStep = (100 - $this->progressPercent) / $totalNumberOfFilters;

    $this->createNewFilters($allCRFilters, $shopTags);
    $this->reportAlive();
    $this->deleteFilters($allCRFilters, $shopTags);

    $this->reportProgress(100);
  }

  /**
   * @param \CleverReach\BusinessLogic\Utility\Filter[] $allCRFilters
   * @param \CleverReach\BusinessLogic\Entity\TagCollection $shopTags
   *
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
   */
  private function createNewFilters($allCRFilters, $shopTags) {
    // Convert array of filter to array of strings.
    $allFilterNames = [];
    foreach ($allCRFilters as $filter) {
      $allFilterNames[] = $filter->getFirstCondition();
    }

    foreach ($shopTags as $tag) {
      if (!in_array((string) $tag, $allFilterNames, FALSE)) {
        $this->createFilter($tag);
      }

      $this->incrementProgress();
    }
  }

  /**
   * @param \CleverReach\BusinessLogic\Utility\Filter[] $allCRFilters
   * @param \CleverReach\BusinessLogic\Entity\TagCollection $shopTags
   *
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
   */
  private function deleteFilters($allCRFilters, $shopTags) {
    if (empty($allCRFilters)) {
      return;
    }

    foreach ($allCRFilters as $filter) {
      $tagName = $filter->getFirstCondition();
      if (!$shopTags->hasTag($tagName)
        && strpos($tagName, $this->getConfigService()->getIntegrationName()) === 0
      ) {
        $this->getProxy()->deleteFilter($filter->getId(), $this->integrationId);
      }

      /** @noinspection DisconnectedForeachInstructionInspection */
      $this->incrementProgress();
    }
  }

  /**
   * @param \CleverReach\BusinessLogic\Entity\Tag $tag
   *
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
   */
  private function createFilter($tag) {
    $rule = new Rule('tags', 'contains', (string) $tag);

    $filter = new Filter($tag->getTitle(), $rule);
    $this->getProxy()->createFilter($filter, $this->integrationId);
  }

  /**
   *
   */
  private function incrementProgress() {
    $this->progressPercent += $this->progressStep;
    $this->reportProgress($this->progressPercent);
  }

  /**
   * @return \CleverReach\BusinessLogic\Interfaces\Recipients
   */
  private function getRecipientsService() {
    if ($this->recipientsService === NULL) {
      $this->recipientsService = ServiceRegister::getService(Recipients::CLASS_NAME);
    }

    return $this->recipientsService;
  }

}
