<?php

namespace Drupal\stripe_webhooks\Event;

use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the source event.
 *
 * @see https://stripe.com/docs/api#transaction_object
 */
class SourceTransactionEvent extends Event {

  /**
   * The source.
   *
   * @var \Stripe\Event
   */
  protected $sourceTransaction;

  /**
   * Constructs a new SourceTransactionEvent.
   *
   * @param \Stripe\Event $source_transaction
   *   The source.
   */
  public function __construct(StripeEvent $source_transaction) {
    $this->sourceTransaction = $source_transaction;
  }

  /**
   * Gets the source.
   *
   * @return \Stripe\Event
   *   Returns the source.
   */
  public function getSourceTransaction() {
    return $this->sourceTransaction;
  }

}
