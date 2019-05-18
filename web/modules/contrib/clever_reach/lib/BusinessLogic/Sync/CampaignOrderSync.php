<?php

namespace CleverReach\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Entity\OrderItem;
use CleverReach\BusinessLogic\Interfaces\OrderItems;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Exceptions\SyncTaskFailedException;

/**
 * Class CampaignOrderSync
 *
 * @package CleverReach\BusinessLogic\Sync
 */
class CampaignOrderSync extends BaseSyncTask
{
    const INITIAL_PROGRESS_PERCENT = 10;
    /**
     * Instance of order item service.
     *
     * @var OrderItems
     */
    private $orderItemsService;
    /**
     * Associative array of state data.
     *
     * @var array
     */
    private $stateData;

    /**
     * CampaignOrderSync constructor.
     *
     * @param array $orderItemsIdMailingIdMap Associative array where Order item ID is key and mailing id is value.
     */
    public function __construct(array $orderItemsIdMailingIdMap)
    {
        $this->orderItemsService = $this->getOrderItemsService();
        $this->stateData = array(
            'orderItemsIdMailingIdMap' => $orderItemsIdMailingIdMap,
            'failedOrderItemsIdMailingIdMap' => array(),
            'currentProgress' => self::INITIAL_PROGRESS_PERCENT,
            'numberOfOrderItemsForSync' => count($orderItemsIdMailingIdMap),
        );
    }

    /**
     * Instance of order item service.
     *
     * @return OrderItems
     *   Get instance of order items service.
     */
    private function getOrderItemsService()
    {
        $orderItemsService = $this->orderItemsService;

        if ($orderItemsService === null) {
            $orderItemsService = ServiceRegister::getService(OrderItems::CLASS_NAME);
        }

        return $orderItemsService;
    }

    /**
     * String representation of object
     *
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize($this->stateData);
    }

    /**
     * Constructs the object.
     *
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $this->stateData = unserialize($serialized);
    }

    /**
     * Runs task execution.
     *
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\SyncTaskFailedException
     */
    public function execute()
    {
        $orderItemsForSync = $this->stateData['orderItemsIdMailingIdMap'] +
            $this->stateData['failedOrderItemsIdMailingIdMap'];
        $this->stateData['failedOrderItemsIdMailingIdMap'] = array();
        $orderItemsWithoutMailingIds = $this->getOrderItemsService()->getOrderItems(array_keys($orderItemsForSync));
        $this->reportAlive();

        /** @var OrderItem $orderItem */
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
     * Sets mailing ID to passed OrderItem.
     *
     * @param OrderItem|null $orderItem Order item object.
     */
    private function formatOrderItems($orderItem)
    {
        $mailingId = $this->stateData['orderItemsIdMailingIdMap'][$orderItem->getOrderId()];

        if ($mailingId !== null) {
            $orderItem->setMailingId($mailingId);
        }
    }

    /**
     * Upload order item to CleverReach.
     *
     * @param OrderItem|null $orderItem Order item object.
     */
    private function uploadOrderItemToDestinationSystem($orderItem)
    {
        try {
            $this->getProxy()->uploadOrderItem($orderItem);
        } catch (\Exception $e) {
            $this->stateData['failedOrderItemsIdMailingIdMap'][$orderItem->getOrderId()] =
                $this->stateData['orderItemsIdMailingIdMap'][$orderItem->getOrderId()];
        }
    }

    /**
     * Calculates progress base on synchronized items.
     */
    private function calculateProgress()
    {
        $numberOfSyncedOrdersItems = $this->stateData['numberOfOrderItemsForSync']
            - count($this->stateData['orderItemsIdMailingIdMap']);

        $synchronizedOrdersItemsPercentage = $numberOfSyncedOrdersItems
            * (100 - self::INITIAL_PROGRESS_PERCENT) / $this->stateData['numberOfOrderItemsForSync'];

        $this->stateData['currentProgress'] = self::INITIAL_PROGRESS_PERCENT + $synchronizedOrdersItemsPercentage;
    }

    /**
     * Checks if sending of any order items failed.
     *
     * @throws SyncTaskFailedException
     *   When at least one campaign order sync fails.
     */
    private function checkIfAnyOrderItemFailed()
    {
        if (count($this->stateData['failedOrderItemsIdMailingIdMap']) > 0) {
            $errorMessage = 'Campaign order sync task failed for order items: '
                . implode(', ', array_keys($this->stateData['failedOrderItemsIdMailingIdMap']))
                . '. Current task progress is: '
                . $this->stateData['currentProgress'];

            Logger::logDebug($errorMessage);

            throw new SyncTaskFailedException($errorMessage);
        }
    }
}
