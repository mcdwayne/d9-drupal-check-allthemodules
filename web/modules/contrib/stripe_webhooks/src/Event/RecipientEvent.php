<?php

namespace Drupal\stripe_webhooks\Event;


use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the recipient event.
 *
 * @see https://stripe.com/docs/api#recipient_object
 */
class RecipientEvent extends Event {

  /**
   * The recipient.
   *
   * @var \Stripe\Event
   */
  protected $recipient;

  /**
   * Constructs a new RecipientEvent.
   *
   * @param \Stripe\Event $recipient
   *   The recipient.
   */
  public function __construct(StripeEvent $recipient) {
    $this->recipient = $recipient;
  }

  /**
   * Gets the recipient.
   *
   * @return \Stripe\Event
   *   Returns the recipient.
   */
  public function getRecipient() {
    return $this->recipient;
  }

}
