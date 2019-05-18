<?php

namespace CleverReach\BusinessLogic\Interfaces;

/**
 *
 */
interface OrderItems {
  const CLASS_NAME = __CLASS__;

  /**
   * @param [string] $orderItemsIds
   * @return  [OrderItems]
   */
  public function getOrderItems($orderItemsIds);

}
