<?php

namespace Drupal\commerce_google_analytics\EventSubscriber;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Sends Ecommerce tracking data to google analytics when an order is placed.
 */
class SendOrderAnalyticsSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['commerce_order.place.post_transition'] = ['sendOrderAnalytics', -100];

    return $events;
  }

  /**
   * Sends the Ecommerce tracking data using GA Push API.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event we subscribed to.
   */
  public function sendOrderAnalytics(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

    // Build the GA Push array.
    $ga_push_params = $this->buildGaPushParams($order);

    // Push a GA ecommerce using GA Push module API.
    // @see ga_push_add_ecommerce()
    ga_push_add_ecommerce($ga_push_params);
  }

  /**
   * Builds the Ecommerce Tracking data array needed by GA Push API for a
   * given order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function buildGaPushParams(OrderInterface $order) {
    $order_total = $order->getTotalPrice();
    $currency_code = $order_total->getCurrencyCode();
    /** @var \Drupal\profile\Entity\ProfileInterface $billing_profile */
    $billing_profile = $order->getBillingProfile();
    /** @var \Drupal\address\AddressInterface $address */
    $address = $billing_profile->get('address')->first();

    // ToDo: tax total.
    $tax_total = 0;

    // Check if the commerce_shipping module is enabled and generate the total
    // shipping cost of the order.
    $shipping_total = 0;
    if (\Drupal::moduleHandler()->moduleExists('commerce_shipping')) {
      $shipping_adjustments_total = new Price('0', $currency_code);
      foreach ($order->collectAdjustments() as $adjustment) {
        if ($adjustment->getType() == 'shipping') {
          $shipping_adjustments_total->add($adjustment->getAmount());
        }
      }
      $shipping_total = $shipping_adjustments_total->getNumber();
    }

    // Build the transaction array.
    $transaction = [
      'order_id' => $order->id(),
      'affiliation' => $order->getStore()->label(),
      'total' => $order_total->getNumber(),
      'currency' => $currency_code,
      'total_tax' => $tax_total,
      'total_shipping' => $shipping_total,
      'city' => $address->getLocality(),
      'region' => $address->getAdministrativeArea(),
      'country' => $address->getCountryCode(),
    ];

    // Allow modules to alter the transaction.
    // They can refer to the order but may not change it.
    $context = ['order' => $order];
    \Drupal::moduleHandler()->alter('commerce_google_analytics_transaction', $transaction, $context);

    $items = [];
    foreach ($order->getItems() as $order_item) {
      $purchased_entity = $order_item->getPurchasedEntity();
      // Build the item arguments.
      $item = [
        'order_id' => $order->id(),
        'sku' => $order_item->id(),
        'name' => $order_item->label(),
        'category' => ucfirst($order_item->bundle()),
        'price' => $order_item->getUnitPrice()->getNumber(),
        'currency' => $order_item->getUnitPrice()->getCurrencyCode(),
        'quantity' => $order_item->getQuantity(),
      ];
      // Check if purchased entity is a product variation and update item data.
      if ($purchased_entity instanceof ProductVariationInterface) {
        $item['sku'] = $purchased_entity->getSku();
        $item['sku'] = 'Product: ' . $purchased_entity->bundle();
      }

      // Allow modules to alter the item arguments.
      $context = [
        'transaction' => $transaction,
        'order' => $order,
      ];
      \Drupal::moduleHandler()->alter('commerce_google_analytics_item', $item, $order_item, $context);

      // If the item has been removed (empty) from drupal_alter do not include:
      if (!empty($item)) {
        $items[] = $item;
      }
    }

    // Allow modules to alter the final items array.
    $context = [
      'transaction' => $transaction,
      'order' => $order,
    ];
    \Drupal::moduleHandler()->alter('commerce_google_analytics_items', $items, $context);

    $ga_push_params = [
      'trans' => $transaction,
      'items' => $items,
    ];

    return $ga_push_params;
  }

}
