<?php

namespace Drupal\stripe_api\Event;

use Stripe\Event as StripeEvent;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class StripeApiWebhookEvent.
 *
 * Provides the Stripe API Webhook Event.
 */
class StripeApiWebhookEvent extends Event {

  /**
   * @var string*/
  public $type;
  /**
   * @var array*/
  public $data;
  /**
   * @var \Stripe\Event*/
  public $event;

  /**
   * Sets the default values for the event.
   *
   * @param string $type
   *   Webhook event type.
   * @param array $data
   *   Webhook event data.
   * @param \Stripe\Event $event
   *   Stripe event object.
   */
  public function __construct($type, $data, StripeEvent $event = NULL) {
    $this->type = $type;
    $this->data = $data;
    $this->event = $event;
  }

}
