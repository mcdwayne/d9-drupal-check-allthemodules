<?php

namespace Drupal\shopify\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\shopify\Event\ShopifyWebhookEvent;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ShopifyWebhook.
 *
 * Provides the route functionality for shopify.webhook route.
 */
class ShopifyWebhook extends ControllerBase {

  /**
   * Captures the incoming webhook request.
   */
  public function handleIncomingWebhook() {
    $client = shopify_api_client();
    $data = $client->getIncomingWebhook($validate = TRUE);
    $topic = \Drupal::request()->headers->get('x-shopify-topic');

    // Dispatch the webhook event.
    $dispatcher = \Drupal::service('event_dispatcher');
    $e = new ShopifyWebhookEvent($topic, $data);
    $dispatcher->dispatch('shopify.webhook', $e);

    // Everything is okay.
    return new Response('Okay', Response::HTTP_OK);
  }

}
