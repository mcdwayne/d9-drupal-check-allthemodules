<?php

namespace CleverReach\BusinessLogic\Interfaces;

/**
 * Interface OrderItems
 *
 * @package CleverReach\BusinessLogic\Interfaces
 */
interface OrderItems
{
    const CLASS_NAME = __CLASS__;

    /**
     * Gets order items by passed IDs.
     *
     * @param string[]|null $orderItemsIds Array of order item IDs that needs to be fetched.
     *
     * @return OrderItems[]
     *   Array of OrderItems that matches passed IDs.
     */
    public function getOrderItems($orderItemsIds);
}
