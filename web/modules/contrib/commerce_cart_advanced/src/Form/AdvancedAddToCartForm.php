<?php

namespace Drupal\commerce_cart_advanced\Form;

use Drupal\commerce_cart\Form\AddToCartForm;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a replacement for the order item add to cart form.
 *
 * It ensures that the product variation is added to a current cart.
 */
class AdvancedAddToCartForm extends AddToCartForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    ContentEntityForm::submitForm($form, $form_state);

    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $this->entity;
    /** @var \Drupal\commerce\PurchasableEntityInterface $purchased_entity */
    $purchased_entity = $order_item->getPurchasedEntity();

    // Create the cart if no appropriate current cart exists.
    $order_type_id = $this->orderTypeResolver->resolve($order_item);
    $store = $this->selectStore($purchased_entity);
    $cart = $this->cartProvider->getCurrentCart($order_type_id, $store);

    if (!$cart) {
      $cart = $this->cartProvider->createCart($order_type_id, $store);
    }

    // Add the order item to the cart.
    $this->entity = $this->cartManager->addOrderItem(
      $cart,
      $order_item,
      $form_state->get(['settings', 'combine'])
    );

    // Other submit handlers might need the cart ID.
    $form_state->set('cart_id', $cart->id());
  }

}
