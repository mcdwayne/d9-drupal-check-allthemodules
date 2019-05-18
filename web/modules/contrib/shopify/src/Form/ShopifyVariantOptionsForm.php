<?php

namespace Drupal\shopify\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\shopify\Entity\ShopifyProduct;
use Drupal\shopify\Entity\ShopifyProductVariant;

/**
 * Class ShopifyVariantOptionsForm.
 *
 * @package Drupal\shopify\Form
 */
class ShopifyVariantOptionsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shopify_variant_options_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ShopifyProduct $product = NULL) {
    // Disable caching of this form.
    $form['#cache']['max-age'] = 0;
    $options = $product->options->get(0)->toArray();
    $form_state->set('product', $product);

    $variant_id = \Drupal::request()->get('variant_id', FALSE);
    $default_options = \Drupal::request()->get('options', []);

    if (empty($default_options) && $variant_id) {
      // No options set from the query.
      // Set default options from the active variant.
      $variant = ShopifyProductVariant::loadByVariantId($variant_id);
      $variant_options = $variant->getFormattedOptions();
      foreach ($options as $option) {
        foreach ($option->values as $option_value) {
          if (in_array($option_value, $variant_options)) {
            $default_options[$option->id] = $option_value;
          }
        }
      }
    }

    $form['options']['#tree'] = TRUE;

    foreach ($options as $option) {
      if ($option->values[0] == 'Default Title') {
        // Skip variant options that don't really need options.
        continue;
      }
      $form['options'][$option->id] = [
        '#type' => 'select',
        '#options' => array_combine($option->values, $option->values),
        '#title' => t($option->name),
        '#default_value' => isset($default_options[$option->id]) ? $default_options[$option->id] : '',
        '#attributes' => ['onchange' => 'javascript:this.form.update_variant.click();'],
      ];
    }
    $form['update_variant'] = [
      '#type' => 'submit',
      '#value' => t('Update'),
      '#name' => 'update_variant',
      '#attributes' => ['style' => 'display:none;'],
    ];

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
    $this->goToVariantWithOptions($form_state->getValue('options'), $form_state);
  }

  /**
   * Redirects the page to the product with a variant selected.
   *
   * @param array $options
   *   Options from the form_state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   *   TODO: Move $options to the end.
   */
  private function goToVariantWithOptions(array $options = [], FormStateInterface $form_state) {
    $variant = $this->getVariantByOptions($form_state, $options);
    if ($variant instanceof ShopifyProductVariant) {
      // We have a matching variant we can redirect to.
      $form_state->setRedirect('entity.shopify_product.canonical', [
        'shopify_product' => $form_state->get('product')
          ->id(),
      ], [
        'query' => [
          'variant_id' => $variant->variant_id->value,
          'options' => $options,
        ],
      ]);
    }
    else {
      // No variant matches.
      $form_state->setRedirect('entity.shopify_product.canonical', [
        'shopify_product' => $form_state->get('product')
          ->id(),
      ], [
        'query' => [
          'variant_id' => 0,
          'options' => $options,
        ],
      ]);
    }
  }

  /**
   * Gets a variant that has options matching the passed option values.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   * @param array $options
   *   Options from the form_state.
   *
   * @return \Drupal\shopify\Entity\ShopifyProductVariant
   *   Shopify product variant.
   */
  private function getVariantByOptions(FormStateInterface $form_state, array $options = []) {
    $valid_variant = NULL;
    foreach ($form_state->get('product')->variants as $variant) {
      $variant = ShopifyProductVariant::load($variant->target_id);
      if ($variant instanceof ShopifyProductVariant) {
        // Determine if this variants options match all of the passed options.
        foreach ($options as $option) {
          if (!in_array($option, $variant->getFormattedOptions())) {
            continue 2;
          }
        }
        $valid_variant = $variant;
      }
    }
    return $valid_variant;
  }

}
