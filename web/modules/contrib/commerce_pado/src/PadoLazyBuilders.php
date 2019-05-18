<?php

namespace Drupal\commerce_pado;

use Drupal\Core\Form\FormState;
use Drupal\commerce_product\ProductLazyBuilders;

/**
 * Provides #lazy_builder callbacks.
 */
class PadoLazyBuilders extends ProductLazyBuilders {

  /**
   * Builds the add to cart form.
   *
   * @param string $product_id
   *   The product ID.
   * @param string $view_mode
   *   The view mode used to render the product.
   * @param bool $combine
   *   TRUE to combine order items containing the same product variation.
   * @param string $add_on_field
   *   The machine name of the product entity reference field to add-ons.
   * @param bool multiple
   *   Whether the customer is able to add more add-ons at a time.
   *
   * @return array
   *   A renderable array containing the cart form.
   */
  public function addToCartWithAddOnsForm($product_id, $view_mode, $combine, $add_on_field, $multiple) {
    /** @var \Drupal\commerce_order\OrderItemStorageInterface $order_item_storage */
    $order_item_storage = $this->entityTypeManager->getStorage('commerce_order_item');
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $this->entityTypeManager->getStorage('commerce_product')->load($product_id);
    $default_variation = $product->getDefaultVariation();
    if (!$default_variation) {
      return [];
    }

    $order_item = $order_item_storage->createFromPurchasableEntity($default_variation);
    /** @var \Drupal\commerce_cart\Form\AddToCartFormInterface $form_object */
    $form_object = $this->entityTypeManager->getFormObject('commerce_order_item', 'pado_add_to_cart');
    $form_object->setEntity($order_item);
    // The default form ID is based on the variation ID, but in this case the
    // product ID is more reliable (the default variation might change between
    // requests due to an availability change, for example).
    $form_object->setFormId($form_object->getBaseFormId() . '_commerce_product_' . $product_id);
    $form_state = (new FormState())->setFormState([
      'product' => $product,
      'view_mode' => $view_mode,
      'settings' => [
        'combine' => $combine,
        'add_on_field' => $add_on_field,
        'multiple' => $multiple,
      ],
    ]);

    return $this->formBuilder->buildForm($form_object, $form_state);
  }

}
