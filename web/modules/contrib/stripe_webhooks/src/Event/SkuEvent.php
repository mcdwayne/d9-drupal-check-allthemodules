<?php

namespace Drupal\stripe_webhooks\Event;


use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the sku event.
 *
 * @see https://stripe.com/docs/api#sku_object
 */
class SkuEvent extends Event {

  /**
   * The sku.
   *
   * @var \Stripe\Event
   */
  protected $sku;

  /**
   * Constructs a new SkuEvent.
   *
   * @param \Stripe\Event $sku
   *   The sku.
   */
  public function __construct(StripeEvent $sku) {
    $this->sku = $sku;
  }

  /**
   * Gets the sku.
   *
   * @return \Stripe\Event
   *   Returns the sku.
   */
  public function getSku() {
    return $this->sku;
  }

}
