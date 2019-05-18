<?php

namespace Drupal\bigcommerce\EventSubscriber;

use BigCommerce\Api\v3\Model\CartUpdateRequest;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_cart\Event\CartEntityDeleteEvent;
use Drupal\commerce_cart\Event\CartEntityAddEvent;
use Drupal\commerce_cart\Event\CartOrderItemRemoveEvent;
use Drupal\commerce_cart\Event\CartOrderItemUpdateEvent;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\commerce_order\Entity\OrderInterface;

use BigCommerce\Api\v3\Api\CartApi;
use BigCommerce\Api\v3\Model\CartRequestData;
use BigCommerce\Api\v3\Model\LineItemRequestData;
use BigCommerce\Api\v3\Model\Cart;

/**
 * Event Subscriber to handle syncing the Commerce and BigCommerce carts.
 */
class CartEventSubscriber implements EventSubscriberInterface {

  /**
   * The BigCommerce API settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a new CartEventSubscriber object.
   *
   * @param \BigCommerce\Api\v3\Api\CartApi $cart_api
   *   The cart API.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(CartApi $cart_api, ConfigFactoryInterface $config_factory) {
    $this->cartApi = $cart_api;
    $this->config = $config_factory->get('bigcommerce.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      CartEvents::CART_EMPTY => 'bigCommerceCartDelete',
      CartEvents::CART_ENTITY_ADD => 'bigCommerceAddToCart',
      CartEvents::CART_ORDER_ITEM_UPDATE => 'bigCommerceCartUpdate',
      CartEvents::CART_ORDER_ITEM_REMOVE => 'bigCommerceCartRemove',
    ];
    return $events;
  }

  /**
   * Delete BigCommerce cart.
   *
   * @param \Drupal\commerce_cart\Event\CartEntityDeleteEvent $event
   *   The add to cart event.
   */
  public function bigCommerceCartDelete(CartEntityDeleteEvent $event) {
    try {
      $order = $event->getCart();
      $bc_cart_id = $order->getData('bigcommerce_cart_id');

      if ($bc_cart_id) {
        $this->cartApi->cartsCartIdDelete($bc_cart_id);
      }
    }
    catch (\Exception $e) {
      // Watchdog? not sure what we should do here.
      throw $e;
    }
  }

  /**
   * Push the added item to BigCommerce cart.
   *
   * @param \Drupal\commerce_cart\Event\CartEntityAddEvent $event
   *   The add to cart event.
   */
  public function bigCommerceAddToCart(CartEntityAddEvent $event) {
    try {
      $order_item = $event->getOrderItem();
      $order = $event->getCart();
      $bc_cart_id = $order->getData('bigcommerce_cart_id');

      $request_data = new CartRequestData();
      $request_data->setChannelId($this->config->get('channel_id'));
      $request_data->setLineItems([
        new LineItemRequestData([
          'quantity' => $order_item->getQuantity(),
          'product_id' => $order_item->getPurchasedEntity()->getProduct()->bigcommerce_id->value,
          'variant_id' => $order_item->getPurchasedEntity()->bigcommerce_id->value,
        ]),
      ]);

      // Probably turn this into a function so we can stop caring about if we
      // have a cart or not.
      if (!$bc_cart_id) {
        $cart_response = $this->cartApi->cartsPost($request_data);
        if ($cart_response) {
          $bc_cart = $cart_response->getData();
        }
        $bc_cart_id = $bc_cart->getId();
        $order->setData('bigcommerce_cart_id', $bc_cart_id);
      }
      else {
        try {
          $cart_response = $this->cartApi->cartsCartIdItemsPost($bc_cart_id, $request_data);
          if ($cart_response) {
            $bc_cart = $cart_response->getData();
          }
        }
        catch (\Exception $e) {
          $cart_response = $this->cartApi->cartsPost($request_data);
          if ($cart_response) {
            $bc_cart = $cart_response->getData();
          }
          $bc_cart_id = $bc_cart->getId();
          $order->setData('bigcommerce_cart_id', $bc_cart_id);
        }
      }

      $bc_line_items = $this->getBigCommerceLineItems($bc_cart);
      foreach ($bc_line_items as $bc_line_item) {
        if ($bc_line_item->getProductId() == $order_item->getPurchasedEntity()->getProduct()->bigcommerce_id->value
          && $bc_line_item->getVariantId() == $order_item->getPurchasedEntity()->bigcommerce_id->value) {
          $order_item->setData('bigcommerce_item_id', $bc_line_item->getId());
          $order_item->save();
          break;
        }
      }

      $this->crossCheckCart($order, $bc_cart);
    }
    catch (\Exception $e) {
      // Watchdog? not sure what we should do here.
      throw $e;
    }
  }

  /**
   * Update an item in BigCommerce cart via item_id.
   *
   * @param \Drupal\commerce_cart\Event\CartOrderItemUpdateEvent $event
   *   The add to cart event.
   */
  public function bigCommerceCartUpdate(CartOrderItemUpdateEvent $event) {
    try {
      $order_item = $event->getOrderItem();
      $order = $event->getCart();
      $bc_cart_id = $order->getData('bigcommerce_cart_id');
      $bc_item_id = $order_item->getData('bigcommerce_item_id');

      $request_data = new CartUpdateRequest([
        'line_item' => new LineItemRequestData([
          'product_id' => $order_item->getPurchasedEntity()->getProduct()->bigcommerce_id->value,
          'variant_id' => $order_item->getPurchasedEntity()->bigcommerce_id->value,
          'quantity' => $order_item->getQuantity(),
        ]),
      ]);

      if ($bc_cart_id) {
        $this->cartApi->cartsCartIdItemsItemIdPut($bc_cart_id, $bc_item_id, $request_data);
      }
    }
    catch (\Exception $e) {
      // Watchdog? not sure what we should do here.
      throw $e;
    }
  }

  /**
   * Remove an item from BigCommerce cart via item_id.
   *
   * @param \Drupal\commerce_cart\Event\CartOrderItemRemoveEvent $event
   *   The add to cart event.
   */
  public function bigCommerceCartRemove(CartOrderItemRemoveEvent $event) {
    try {
      $order_item = $event->getOrderItem();
      $order = $event->getCart();
      $bc_cart_id = $order->getData('bigcommerce_cart_id');
      $bc_item_id = $order_item->getData('bigcommerce_item_id');

      if ($bc_cart_id) {
        $this->cartApi->cartsCartIdItemsItemIdDelete($bc_cart_id, $bc_item_id);
      }
    }
    catch (\Exception $e) {
      // Watchdog? not sure what we should do here.
      throw $e;
    }
  }

  /**
   * Check and sync the Drupal Commerce and BigCommerce carts.
   *
   * @param \Drupal\commerce\commerce_order\entity\OrderInterface $cart
   *   The Drupal Commerce cart, which is actually an order.
   * @param \BigCommerce\Api\v3\Model\Cart $bc_cart
   *   The BigCommerce cart returned by the API.
   */
  protected function crossCheckCart(OrderInterface $cart, Cart $bc_cart) {
    $bc_cart_id = $cart->getData('bigcommerce_cart_id');
    $bc_line_items = $this->getBigCommerceLineItems($bc_cart);

    // Check if any products exist in the DC cart and not the BC cart
    // and correct if needed.
    foreach ($cart->getItems() as $order_item) {
      if ($order_item->getData('bigcommerce_item_id')) {
        foreach ($bc_line_items as $bc_line_item) {
          if ($bc_line_item->getId() == $order_item->getData('bigcommerce_item_id')) {
            continue 2;
          }
        }
      }

      $request_data = new CartRequestData();
      $request_data->setChannelId($this->config->get('channel_id'));
      $request_data->setLineItems([
        new LineItemRequestData([
          'quantity' => $order_item->getQuantity(),
          'product_id' => $order_item->getPurchasedEntity()->getProduct()->bigcommerce_id->value,
          'variant_id' => $order_item->getPurchasedEntity()->bigcommerce_id->value,
        ]),
      ]);

      $cart_response = $this->cartApi->cartsCartIdItemsPost($bc_cart_id, $request_data);
      if ($cart_response) {
        $bc_cart = $cart_response->getData();
      }

      $bc_line_items = $this->getBigCommerceLineItems($bc_cart);
      foreach ($bc_line_items as $bc_line_item) {
        if ($bc_line_item->getProductId() == $order_item->getPurchasedEntity()->getProduct()->bigcommerce_id->value
          && $bc_line_item->getVariantId() == $order_item->getPurchasedEntity()->bigcommerce_id->value) {
          $order_item->setData('bigcommerce_item_id', $bc_line_item->getId());
          $order_item->save();
          break;
        }
      }
    }

    // Check BC cart and remove any items not also in the DC cart.
    $bc_line_items = $this->getBigCommerceLineItems($bc_cart);
    foreach ($bc_line_items as $bc_line_item) {
      foreach ($cart->getItems() as $order_item) {
        if ($bc_line_item->getId() == $order_item->getData('bigcommerce_item_id')) {
          continue 2;
        }
      }
      $this->cartApi->cartsCartIdItemsItemIdDelete($bc_cart_id, $bc_line_item->getId());
    }
  }

  /**
   * Get a merged list of all regular products from a BigCommerce cart.
   *
   * @param \BigCommerce\Api\v3\Model\Cart $bc_cart
   *   The returned BC cart object.
   *
   * @return \BigCommerce\Api\v3\Model\LineItems
   *   The merged line items, excluding gift cards.
   */
  private function getBigCommerceLineItems(Cart $bc_cart) {
    $bc_line_items = $bc_cart->getLineItems();
    // Don't include gift certificates because they don't have Product IDs.
    $bc_line_items = array_merge($bc_line_items->getPhysicalItems(), $bc_line_items->getDigitalItems());

    return $bc_line_items;
  }

}
