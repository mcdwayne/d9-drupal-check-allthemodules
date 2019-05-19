<?php

namespace Drupal\stripe_webhooks\Event;


use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the product event.
 *
 * @see https://stripe.com/docs/api#product_object
 */
class ProductEvent extends Event {

  /**
   * The product.
   *
   * @var \Stripe\Event
   */
  protected $product;

  /**
   * Constructs a new ProductEvent.
   *
   * @param \Stripe\Event $product
   *   The product.
   */
  public function __construct(StripeEvent $product) {
    $this->product = $product;
  }

  /**
   * Gets the product.
   *
   * @return \Stripe\Event
   *   Returns the product.
   */
  public function getProduct() {
    return $this->product;
  }

}
