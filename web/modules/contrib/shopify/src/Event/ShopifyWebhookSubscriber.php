<?php

namespace Drupal\shopify\Event;

use Drupal\shopify\Entity\ShopifyProduct;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ShopifyWebhookSubscriber.
 *
 * Provides the webhook subscriber functionality.
 */
class ShopifyWebhookSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['shopify.webhook'][] = ['onIncomingWebhook'];
    return $events;
  }

  /**
   * Process an incoming webhook.
   *
   * @param \Drupal\shopify\Event\ShopifyWebhookEvent $event
   *   Logs an incoming webhook of the setting is on.
   */
  public function onIncomingWebhook(ShopifyWebhookEvent $event) {
    $config = \Drupal::config('shopify.webhooks');
    if ($config->get('log_webhooks')) {
      // Log this incoming webhook data.
      \Drupal::logger('shopify.webhook')->info(t('<strong>Topic:</strong> @topic<br />
      <strong>Data:</strong> @data.', [
        '@topic' => $event->topic,
        '@data' => var_export($event->data, TRUE),
      ]));
    }
    $method = 'webhook_' . str_replace('/', '_', $event->topic);
    if (method_exists($this, $method)) {
      $this->{$method}($event->data);
    }
  }

  /**
   * Handle updating of products.
   */
  private function webhook_products_update(\stdClass $data) {
    $entity = ShopifyProduct::loadByProductId($data->id);
    if ($entity instanceof ShopifyProduct) {
      $entity->update((array) $data);
      $entity->save();
    }
  }

  /**
   * Handle creating of products.
   */
  private function webhook_products_create(\stdClass $data) {
    $entity = ShopifyProduct::create((array) $data);
    $entity->save();
  }

  /**
   * Handle deleting of products.
   */
  private function webhook_products_delete(\stdClass $data) {
    $entity = ShopifyProduct::loadByProductId($data->id);
    if ($entity instanceof ShopifyProduct) {
      $entity->delete();
    }
  }

  /**
   * Handle creating of collections.
   */
  private function webhook_collections_create(\stdClass $data) {
    shopify_collection_create($data, TRUE);
  }

  /**
   * Handle updating of collections.
   */
  private function webhook_collections_update(\stdClass $data) {
    // Note: This does not currently get hit because of a bug in Shopify.
    // See this issue for updates: https://www.drupal.org/node/2481105
    shopify_collection_update($data, TRUE);
  }

  /**
   * Handle deleting of collections.
   */
  private function webhook_collections_delete(\stdClass $data) {
    $entity = shopify_collection_load($data->id);
    if ($entity instanceof Term) {
      $entity->delete();
    }
  }

}
