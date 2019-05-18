<?php

namespace Drupal\commerce_combine_carts\EventSubscriber;

use Drupal\commerce_combine_carts\CartUnifier;
use Drupal\commerce_order\Event\OrderAssignEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CartEventSubscriber
 *
 * @package Drupal\commerce_customizations\EventSubscriber
 */
class CartEventSubscriber implements EventSubscriberInterface {

  /** @var CartUnifier */
  protected $cartUnifier;

  /**
   * CartEventSubscriber constructor.
   *
   * @param \Drupal\commerce_combine_carts\CartUnifier $cart_unifier
   *   The cart unifier service.
   */
  public function __construct(CartUnifier $cart_unifier) {
    $this->cartUnifier = $cart_unifier;
  }

  /**
   * @inheritdoc
   */
  static function getSubscribedEvents() {
    $events = [];
    $events[OrderEvents::ORDER_ASSIGN][] = ['onOrderAssign'];
    return $events;
  }

  /**
   * React when an order is being assigned to a user.
   *
   * @param \Drupal\commerce_order\Event\OrderAssignEvent $event
   *   The event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onOrderAssign(OrderAssignEvent $event) {
    $order = $event->getOrder();

    if (!$order->get('cart')->isEmpty() && $order->get('cart')->value) {
      $this->cartUnifier->assignCart($order, $event->getAccount());
    }
  }

}
