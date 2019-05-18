<?php

namespace Drupal\commerce_cart_refresh\EventSubscriber;

use Drupal\commerce_cart_refresh\Event\CartItemQuantityChangeEvent;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\commerce_order\Event\OrderItemEvent;
use Drupal\commerce_product\Event\ProductEvents;
use Drupal\commerce_product\Event\ProductVariationAjaxChangeEvent;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber that handles cloning through the Replicate module.
 */
class ProductEventsSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Event fired when filtering variations.
   *
   * @param \Drupal\commerce_product\Event\ProductVariationAjaxChangeEvent $event
   *
   * @todo Deprecated. Patch not accepted. Need to find another way to act on the AddToCart form.
   */
  public function onVariationAjaxChange(ProductVariationAjaxChangeEvent $event) {
    // Defaults.
    $quantity  = 1;
    $ccrm      = \Drupal::service('commerce_cart_refresh.manager');
    $response  = $event->getResponse();
    $variation = $event->getProductVariation();

    $vid = $variation->id();

    // Try to extract the $form_state from event.
    // You need to apply this patch: https://www.drupal.org/project/commerce/issues/3019102
    if (method_exists($event, 'getFormState')) {
      $form_state = $event->getFormState();
      $input      = $form_state->getUserInput();
      $quantity   = isset($input['quantity'][0]['value']) ? (float) $input['quantity'][0]['value'] : $qty;
    }

    if ($quantity > 1) {
      // Update the price based on quantity.
      $price_dom_selector = $ccrm->getPriceDomSelector($variation);
      $calculated_price   = $ccrm->getCalculatedPrice($quantity, $variation);

      // Remove existing Price update commands.
      $commands = &$response->getCommands();
      foreach ($commands as $delta => $command) {
        if ($command['selector'] == '.' . $price_dom_selector) {
          unset($commands[$delta]);
        }
      }

      // Add custom Price update command.
      $response->addCommand(new ReplaceCommand('#' . $price_dom_selector, [
        '#prefix' => '<span id="' . $price_dom_selector . '">',
        '#markup' => $calculated_price,
        '#suffix' => '</span>',
      ]));
    }
  }

  /**
   * Trigger the quantity change event when user insert a line.
   *
   * @param \Drupal\commerce_order\Event\OrderItemEvent $event
   *   The order item event.
   */
  public function onCartItemInsert(OrderItemEvent $event) {
    $item = $event->getOrderItem();
    if ($item && $item instanceof OrderItemInterface) {
      // A new line was added. Tell the world quantities have changed.
      $event            = new CartItemQuantityChangeEvent($event);
      $event_dispatcher = \Drupal::service('event_dispatcher');
      $event_dispatcher->dispatch(CartItemQuantityChangeEvent::QUANTITY_CHANGE, $event);
    }
  }

  /**
   * Trigger the quantity change event when user insert or update a line.
   *
   * @param \Drupal\commerce_order\Event\OrderItemEvent $event
   *   The order item event.
   */
  public function onCartItemUpdate(OrderItemEvent $event) {
    $item = $event->getOrderItem();
    if ($item && $item instanceof OrderItemInterface) {
      $diff = $item->getQuantity() - $item->original->getQuantity();
      if ($diff != 0) {
        // A quantity was updated. Tell the world quantities have changed.
        $event            = new CartItemQuantityChangeEvent($event);
        $event_dispatcher = \Drupal::service('event_dispatcher');
        $event_dispatcher->dispatch(CartItemQuantityChangeEvent::QUANTITY_CHANGE, $event);
      }
    }
  }

  /**
   * Trigger the quantity change event when user delete a line.
   *
   * @param \Drupal\commerce_order\Event\OrderItemEvent $event
   *   The order item event.
   */
  public function onCartItemDelete(OrderItemEvent $event) {
    // A line was deleted. Tell the world quantities have changed.      
    $event            = new CartItemQuantityChangeEvent($event);
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch(CartItemQuantityChangeEvent::QUANTITY_CHANGE, $event);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Cart-related events.
    $events[ProductEvents::PRODUCT_VARIATION_AJAX_CHANGE][] = ['onVariationAjaxChange', 999];

    // Quantity-related events.
    $events[OrderEvents::ORDER_ITEM_INSERT][] = ['onCartItemInsert'];
    $events[OrderEvents::ORDER_ITEM_UPDATE][] = ['onCartItemUpdate'];
    $events[OrderEvents::ORDER_ITEM_DELETE][] = ['onCartItemDelete'];
    return $events;
  }

}
