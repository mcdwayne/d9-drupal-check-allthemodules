<?php

namespace CleverReach\BusinessLogic\Sync;

/**
 *
 */
class DeletePrefixedFilterSyncTask extends BaseSyncTask {
  const INITIAL_PROGRESS_PERCENT = 10;

  /**
   * @var array
   */
  private $prefixedShopTags;
  /**
   * @var int
   */
  private $progressPercent = self::INITIAL_PROGRESS_PERCENT;
  /**
   * @var int
   */
  private $progressStep;

  /**
   * DeletePrefixedFilterSyncTask constructor.
   *
   * @param array $prefixedShopTags
   */
  public function __construct($prefixedShopTags) {
    $this->prefixedShopTags = $prefixedShopTags;
  }

  /**
   * @inheritdoc
   */
  public function serialize() {
    return serialize($this->prefixedShopTags);
  }

  /**
   * @inheritdoc
   */
  public function unserialize($serialized) {
    $this->prefixedShopTags = unserialize($serialized);
  }

  /**
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
   */
  public function execute() {
    if (empty($this->prefixedShopTags)) {
      $this->reportProgress(100);
      return;
    }

    $this->reportProgress($this->progressPercent);
    $integrationId = $this->getConfigService()->getIntegrationId();
    $this->reportAlive();
    $allCRFilters = $this->getProxy()->getAllFilters($integrationId);
    if (empty($allCRFilters)) {
      $this->reportProgress(100);
      return;
    }

    $this->progressStep = (100 - $this->progressPercent) / count($allCRFilters);
    $this->deleteFilters($allCRFilters, $integrationId);

    $this->reportProgress(100);
  }

  /**
   * @param \CleverReach\BusinessLogic\Utility\Filter[] $allCRFilters
   * @param string $integrationId
   *
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
   * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
   */
  private function deleteFilters($allCRFilters, $integrationId) {
    foreach ($allCRFilters as $filter) {
      if (in_array($filter->getFirstCondition(), $this->prefixedShopTags, TRUE)) {
        $this->getProxy()->deleteFilter($filter->getId(), $integrationId);
      }

      /** @noinspection DisconnectedForeachInstructionInspection */
      $this->incrementProgress();
    }
  }

  /**
   *
   */
  private function incrementProgress() {
    $this->progressPercent += $this->progressStep;
    $this->reportProgress($this->progressPercent);
  }

}
