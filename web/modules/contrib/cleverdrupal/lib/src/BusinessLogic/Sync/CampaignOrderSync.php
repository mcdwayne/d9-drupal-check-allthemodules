<?php

namespace CleverReach\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Interfaces\OrderItems;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Exceptions\SyncTaskFailedException;

/**
 *
 */
class CampaignOrderSync extends BaseSyncTask {
  const INITIAL_PROGRESS_PERCENT = 10;
  /**
   * @var \CleverReach\BusinessLogic\Entity\OrderItems
   */
  private $orderItemsService;
  /**
   * @var array
   */
  private $stateData;

  /**
   * CampaignOrderSync constructor.
   *
   * @param array $orderItemsIdMailingIdMap
   */
  public function __construct(array $orderItemsIdMailingIdMap) {
    $this->orderItemsService = $this->getOrderItemsService();
    $this->stateData = [
      'orderItemsIdMailingIdMap' => $orderItemsIdMailingIdMap,
      'failedOrderItemsIdMailingIdMap' => [],
      'currentProgress' => self::INITIAL_PROGRESS_PERCENT,
      'numberOfOrderItemsForSync' => count($orderItemsIdMailingIdMap),
    ];
  }

  /**
   *
   */
  private function getOrderItemsService() {
    $orderItemsService = $this->orderItemsService;

    if (empty($orderItemsService)) {
      $orderItemsService = ServiceRegister::getService(OrderItems::CLASS_NAME);
    }

    return $orderItemsService;
  }

  /**
   * @return string
   */
  public function serialize() {
    return serialize($this->stateData);
  }

  /**
   * @param string $serialized
   */
  public function unserialize($serialized) {
    $this->stateData = unserialize($serialized);
  }

  /**
   * @throws SyncTaskFailedException
   */
  public function execute() {
    $orderItemsForSync = $this->stateData['orderItemsIdMailingIdMap'] + $this->stateData['failedOrderItemsIdMailingIdMap'];
    $this->stateData['failedOrderItemsIdMailingIdMap'] = [];
    $orderItemsWithoutMailingIds = $this->getOrderItemsService()->getOrderItems(array_keys($orderItemsForSync));
    $this->reportAlive();

    /** @var \CleverReach\BusinessLogic\Entity\OrderItem $orderItem */
    foreach ($orderItemsWithoutMailingIds as $orderItem) {
      $this->formatOrderItems($orderItem);
      $this->uploadOrderItemToDestinationSystem($orderItem);
      unset($this->stateData['orderItemsIdMailingIdMap'][$orderItem->getOrderId()]);
      $this->calculateProgress();
      $this->reportProgress($this->stateData['currentProgress']);
    }

    $this->checkIfAnyOrderItemFailed();
    $this->reportProgress(100);
  }

  /**
   * @param \CleverReach\BusinessLogic\Entity\OrderItem $orderItem
   */
  private function formatOrderItems($orderItem) {
    $mailingId = $this->stateData['orderItemsIdMailingIdMap'][$orderItem->getOrderId()];

    if ($mailingId !== NULL) {
      $orderItem->setMailingId($mailingId);
    }
  }

  /**
   * @param \CleverReach\BusinessLogic\Entity\OrderItem $orderItem
   */
  private function uploadOrderItemToDestinationSystem($orderItem) {
    try {
      $this->getProxy()->uploadOrderItem($orderItem);
    }
    catch (\Exception $e) {
      $this->stateData['failedOrderItemsIdMailingIdMap'][$orderItem->getOrderId()] =
                $this->stateData['orderItemsIdMailingIdMap'][$orderItem->getOrderId()];
    }
  }

  /**
   *
   */
  private function calculateProgress() {
    $numberOfSyncedOrdersItems = $this->stateData['numberOfOrderItemsForSync']
            - count($this->stateData['orderItemsIdMailingIdMap'])
            + count($this->stateData['failedOrderItemsIdMailingIdMap']);

    $synchronizedOrdersItemsPercentage = $numberOfSyncedOrdersItems
            * (100 - self::INITIAL_PROGRESS_PERCENT) / $this->stateData['numberOfOrderItemsForSync'];

    $this->stateData['currentProgress'] = self::INITIAL_PROGRESS_PERCENT + $synchronizedOrdersItemsPercentage;
  }

  /**
   * @throws SyncTaskFailedException
   */
  private function checkIfAnyOrderItemFailed() {
    if (count($this->stateData['failedOrderItemsIdMailingIdMap']) > 0) {
      $errorMessage = 'Campaign order sync task failed for order items: ' .
                implode(', ', $this->stateData['failedOrderItemsIdMailingIdMap']) . '. Current task progress is: ' .
                $this->stateData['currentProgress'];

      Logger::logDebug($errorMessage);

      throw new SyncTaskFailedException($errorMessage);
    }
  }

}
