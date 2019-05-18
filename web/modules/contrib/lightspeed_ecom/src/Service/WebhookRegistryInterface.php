<?php

namespace Drupal\lightspeed_ecom\Service;
use Drupal\Core\Url;
use Drupal\lightspeed_ecom\ShopInterface;

/**
 * Interface WebhookRegistryInterface.
 *
 * @package Drupal\lightspeed_ecom
 */
interface WebhookRegistryInterface {

  /**
   * Retrieve the list of webhook for a given shop.
   *
   * @param string|Shop $shop
   *   The shop to retrieve webhooks for.
   *
   * @return Webhook[]
   *   The list of webhooks.
   */
  public function getWebhooks(ShopInterface $shop);

  /**
   * Dispatches a webhook event to all registered listeners.
   *
   * @param WebhookEvent $event
   *   The event to pass to the event handlers/listeners.
   *
   * @return WebhookEvent
   */
  public function dispatch(WebhookEvent $event);

  /**
   * Ensure required webhook on Lightspeed exists and are up to date.
   *
   * @param \Drupal\lightspeed_ecom\ShopInterface $shop
   */
  public function synchronize(ShopInterface $shop);
}
