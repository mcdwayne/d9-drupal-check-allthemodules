<?php

namespace Drupal\commerce_simplestock;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderStockSubscriber implements EventSubscriberInterface {

  const TYPE_ORDER = 'order';
  const TYPE_REFILL = 'refill';
  const TYPE_STOCKTAKING = 'stocktaking';

  const FIELD_STOCK_ORDERTYPE = 'field_simplestock_ordertype';
  const FIELD_STOCK_QUANTITY = 'field_simplestock_quantity';

  const DECIMALS = 3;
  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    $events = ['commerce_order.place.post_transition' => ['handleStock', -200]];
    return $events;
  }

  public function handleStock(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

    $orderStockType = $order->hasField(self::FIELD_STOCK_ORDERTYPE) ?
      $order->get(self::FIELD_STOCK_ORDERTYPE)->first()->getString() : self::TYPE_ORDER;

    foreach ($order->getItems() as $orderItem) {
      $variation = $orderItem->getPurchasedEntity();
      if ($variation instanceof ContentEntityInterface && $variation->hasField(self::FIELD_STOCK_QUANTITY)) {
        $stockItemList = $variation->get(self::FIELD_STOCK_QUANTITY);
        if (!$stockItemList->first()) {
          $stockItemList->set(0, 0);
        }
        $stockValue = $stockItemList->first()->getString();

        $orderQuantity = $orderItem->getQuantity();
        $changeQuantity = FALSE;
        if ($orderStockType === self::TYPE_STOCKTAKING) {
          $stockDecrease = bcsub($stockValue, $orderQuantity, static::DECIMALS);
          $stockValue = $orderQuantity;
          $orderQuantity = $stockDecrease;
          $changeQuantity = TRUE;
        }
        else {
          if ($orderStockType === self::TYPE_REFILL) {
            $orderQuantity = bcsub(0, $orderQuantity, static::DECIMALS);
            $changeQuantity = TRUE;
          }
          $stockValue = bcsub($stockValue, $orderQuantity, static::DECIMALS);
        }

        if ($changeQuantity) {
          $orderItem->setQuantity($orderQuantity);
          $orderItem->save();
        }
        $stockItemList->set(0, $stockValue);
        $variation->save();
      }
    }

  }

}
