<?php

namespace Drupal\bookkeeping\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;

/**
 * Event raised when preparing a transaction posting for an order.
 */
class OrderTransactionEvent extends SimpleTransactionEvent {

  /**
   * The event name for the commerce order transaction event.
   */
  const EVENT = 'bookkeeping_commerce_order_transaction';

  /**
   * The order this relates to.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The original version of the order, if any.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface|null
   */
  protected $originalOrder;

  /**
   * Construct the Order Transaction event.
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
   * @param \Drupal\commerce_order\Entity\OrderInterface|null $original_order
   *   The original order this transaction relates to, if any.
   */
  public function __construct(string $generator, Price $value, string $from, string $to, OrderInterface $order, ?OrderInterface $original_order) {
    parent::__construct($generator, $value, $from, $to);
    $this->order = $order;
    $this->originalOrder = $original_order;
    $this->related[] = $order;
  }

  /**
   * Get the generator sub-type.
   *
   * @return string
   *   The generator sub-type.
   */
  public function getSubType(): string {
    return explode(':', $this->generator, 2)[1];
  }

  /**
   * Get the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  public function getOrder(): OrderInterface {
    return $this->order;
  }

  /**
   * Get the original order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface|null
   *   The order, if any.
   */
  public function getOriginalOrder(): ?OrderInterface {
    return $this->originalOrder;
  }

}
