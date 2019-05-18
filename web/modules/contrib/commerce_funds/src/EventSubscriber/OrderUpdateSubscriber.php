<?php

namespace Drupal\commerce_funds\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;

/**
 * Class OrderPaidSubscriber.
 *
 * @package Drupal\commerce_funds
 */
class OrderUpdateSubscriber implements EventSubscriberInterface {

  /**
   * Constructor.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[OrderEvents::ORDER_UPDATE] = ['updateAccountBalance', 100];
    return $events;
  }

  /**
   * Update account balance.
   *
   * This method is called whenever commerce_order.commerce_order.update is
   * dispatched.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The order event.
   */
  public function updateAccountBalance(OrderEvent $event) {
    $order = $event->getOrder();
    // We don't want to trigger this event if not a deposit.
    // @TODO Improve this with order is paid event.
    // It seems that drupal commerce have issue if the payment
    // is made in another currency (with paypal i.e), then,
    // the order balance is not zero (100$ paid 90€ = 10).
    if ($order->bundle() === 'deposit') {
      if ($order->getState()->getValue()['value'] == 'completed' && !$order->isPaid()) {
        \Drupal::service('commerce_funds.transaction_manager')->addDepositToBalance($order);
      }
    }
  }

}
