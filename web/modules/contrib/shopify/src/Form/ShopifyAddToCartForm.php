<?php

namespace Drupal\shopify\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\shopify\Entity\ShopifyProduct;
use Drupal\shopify\Entity\ShopifyProductVariant;

/**
 * Class ShopifyAddToCartForm.
 *
 * @package Drupal\shopify\Form
 */
class ShopifyAddToCartForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shopify_add_to_cart_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ShopifyProduct $product = NULL) {
    // Disable caching of this form.
    $form['#cache']['max-age'] = 0;

    $form_state->set('product', $product);

    $variant_id = \Drupal::request()->get('variant_id', FALSE);
    if ($variant_id === FALSE) {
      // No variant set yet, setup the default first variant.
      $entity_id = $product->variants->get(0)->getValue()['target_id'];
      $variant = ShopifyProductVariant::load($entity_id);
      $variant_id = $variant->variant_id->value;
    }
    else {
      $variant = ShopifyProductVariant::loadByVariantId($variant_id);
    }

    $form['#action'] = '//' . shopify_shop_info('domain') . '/cart/add';
    $form['#attached']['library'][] = 'shopify/shopify.js';

    // Data attribute used by shopify.js.
    $form['#attributes']['data-variant-id'] = $variant_id;

    // Variant ID to add to the Shopify cart.
    $form['id'] = [
      '#type' => 'hidden',
      '#value' => $variant_id,
    ];

    // Send user back to the site.
    $form['return_to'] = [
      '#type' => 'hidden',
      '#value' => 'back',
    ];

    // Send the quantity value.
    $form['quantity'] = [
      '#type' => 'number',
      '#title' => t('Quantity'),
      '#default_value' => 1,
      '#attributes' => ['min' => 0, 'max' => 999],
    ];

    if (empty($variant_id)) {
      // No variant matches these options.
      $form['submit'] = [
        '#type' => 'button',
        '#disabled' => TRUE,
        '#value' => t('Unavailable'),
        '#name' => 'add_to_cart',
      ];
    }
    else {
      if ($variant->inventory_policy->value == 'continue' || $variant->inventory_quantity->value > 0 || empty($variant->inventory_management->value)) {
        // User can add this variant to their cart.
        $form['submit'] = [
          '#type' => 'submit',
          '#value' => t('Add to cart'),
          '#name' => 'add_to_cart',
        ];
      }
      else {
        // This variant is out of stock.
        $form['submit'] = [
          '#type' => 'submit',
          '#disabled' => TRUE,
          '#value' => t('Out of stock'),
          '#name' => 'add_to_cart',
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
