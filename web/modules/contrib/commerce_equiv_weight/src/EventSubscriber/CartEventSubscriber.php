<?php

namespace Drupal\commerce_equiv_weight\EventSubscriber;

use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\physical\Weight;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Recalculates the equivalency weight for an order when products are updated.
 */
class CartEventSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the CartEventSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      CartEvents::CART_ENTITY_ADD => ['updateEquivalencyWeight', -100],
      CartEvents::CART_ORDER_ITEM_UPDATE => ['updateEquivalencyWeight', -100],
      CartEvents::CART_ORDER_ITEM_REMOVE => ['updateEquivalencyWeight', -100],
    ];
    return $events;
  }

  /**
   * Update the total equivalency weight for the order.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The cart event.
   */
  public function updateEquivalencyWeight(Event $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
    $cart = $event->getCart();
    if (!$cart->hasField(COMMERCE_EQUIV_WEIGHT_FIELD_EQUIVALENCY_WEIGHT)) {
      return;
    }
    $equiv_weight = $this->calculateOrderEquivalencyWeight($cart);
    $cart->set(COMMERCE_EQUIV_WEIGHT_FIELD_EQUIVALENCY_WEIGHT, $equiv_weight)->save();
  }

  /**
   * Helper function to get equivalency weight for order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The commerce order.
   *
   * @return \Drupal\physical\Weight
   *   The equivalency weight for the order.
   */
  protected function calculateOrderEquivalencyWeight(OrderInterface $order) {
    $config = $this->configFactory->get('commerce_equiv_weight.order_settings');
    $max_equiv_weight = $config->get('equiv_weight');
    $unit = $max_equiv_weight['unit'];
    $order_total_weight = new Weight('0', $unit);
    foreach ($order->getItems() as $order_item) {
      /** @var \Drupal\commerce\PurchasableEntityInterface $variation */
      $variation = $order_item->getPurchasedEntity();
      if (!$variation->hasField(COMMERCE_EQUIV_WEIGHT_FIELD_EQUIVALENCY_WEIGHT)) {
        continue;
      }

      /** @var \Drupal\Core\Field\FieldItemList $weight_field */
      $weight_field = $variation->{COMMERCE_EQUIV_WEIGHT_FIELD_EQUIVALENCY_WEIGHT};
      if ($weight_field->isEmpty()) {
        continue;
      }

      /** @var \Drupal\Physical\Weight $order_item_total_weight */
      $order_item_total_weight = $weight_field->first()->toMeasurement();
      $rounded_weight = new Weight(
        commerce_equiv_weight_round($order_item_total_weight->getNumber()),
        $order_item_total_weight->getUnit()
      );
      $total_rounded_weight = $rounded_weight->multiply($order_item->getQuantity());

      $order_item->set(COMMERCE_EQUIV_WEIGHT_FIELD_EQUIVALENCY_WEIGHT, $total_rounded_weight);
      $order_item->save();

      $order_total_weight = $order_total_weight->add($total_rounded_weight);
    }

    return $order_total_weight;
  }

}
