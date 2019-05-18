<?php

namespace Drupal\shopify\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class ShopifyWebhookEvent.
 *
 * Provides the Shopify Webhook Event.
 */
class ShopifyWebhookEvent extends Event {

  public $topic;
  public $data;

  /**
   * Sets the default values for the event.
   *
   * @param string $topic
   *   Webhook topic (event name).
   * @param \stdClass $data
   *   Webhook event data.
   */
  public function __construct($topic, \stdClass $data) {
    $this->topic = $topic;
    $this->data = $data;
  }

}
