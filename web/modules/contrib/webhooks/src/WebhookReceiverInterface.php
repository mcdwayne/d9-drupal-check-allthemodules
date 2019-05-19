<?php

namespace Drupal\webhooks;

/**
 * Webhook receivers catch incoming events and trigger an internal event.
 *
 * The internal event allows any module in the Drupal site to react to remote
 * operations.
 *
 * @package Drupal\webhooks
 */
interface WebhookReceiverInterface {

  /**
   * Receive a webhook.
   *
   * @param string $name
   *   The machine name of a webhook.
   *
   * @return \Drupal\webhooks\Webhook
   *   A webhook object.
   *
   * @throws \Drupal\webhooks\Exception\WebhookIncomingEndpointNotFoundException
   *   Thrown when the webhook endpoint is not found.
   */
  public function receive($name);

}
