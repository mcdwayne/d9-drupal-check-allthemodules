<?php

namespace Drupal\bookkeeping\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_price\Price;

/**
 * Event raised when preparing a transaction posting for a payment.
 */
class PaymentTransactionEvent extends OrderTransactionEvent {

  /**
   * The event name for the commerce payment transaction event.
   */
  const EVENT = 'bookkeeping_commerce_payment_transaction';

  /**
   * The payment this relates to.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentInterface
   */
  protected $payment;

  /**
   * The original payment this relates to, if any.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentInterface|null
   */
  protected $originalPayment;

  /**
   * Construct the Payment Transaction event.
   *
   * @param string $generator
   *   The generator.
   * @param \Drupal\commerce_price\Price $value
   *   The value we are posting.
   * @param string $from
   *   The account to post from (credit).
   * @param string $to
   *   The account to post to (debit).
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order this transaction relates to.
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $order_item
   *   The payment this transaction relates to.
   * @param \Drupal\commerce_payment\Entity\PaymentInterface|null $original_order_item
   *   The original payment this transaction relates to, if any.
   */
  public function __construct(string $generator, Price $value, string $from, string $to, OrderInterface $order, PaymentInterface $order_item, ?PaymentInterface $original_order_item) {
    parent::__construct($generator, $value, $from, $to, $order, NULL);
    $this->payment = $order_item;
    $this->originalPayment = $original_order_item;
    $this->related[] = $order_item;
  }

  /**
   * Get the payment.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface
   *   The payment.
   */
  public function getPayment(): PaymentInterface {
    return $this->payment;
  }

  /**
   * Get the original payment.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface|null
   *   The payment, if any.
   */
  public function getOriginalPayment(): ?PaymentInterface {
    return $this->originalPayment;
  }

}
