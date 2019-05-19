<?php

namespace Drupal\uc_payment\Event;

use Drupal\Core\Session\AccountInterface;
use Drupal\uc_order\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when payment is entered for an order.
 */
class PaymentEnteredEvent extends Event {

  const EVENT_NAME = 'uc_payment_entered';

  /**
   * The order.
   *
   * @var \Drupal\uc_order\OrderInterface
   */
  public $order;

  /**
   * The user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  public $account;

  /**
   * Constructs the object.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   */
  public function __construct(OrderInterface $order, AccountInterface $account) {
    $this->order = $order;
    $this->account = $account;
  }

}
