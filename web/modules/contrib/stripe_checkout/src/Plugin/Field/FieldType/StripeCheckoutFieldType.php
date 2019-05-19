<?php

namespace Drupal\stripe_checkout\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\DecimalItem;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'stripe_checkout_field_type' field type.
 *
 * @FieldType(
 *   id = "stripe_checkout",
 *   label = @Translation("Stripe checkout"),
 *   description = @Translation("Stripe Checkout field. Accepts decimal cost of a purchase as value."),
 *   category = @Translation("Number"),
 *   default_widget = "number",
 *   default_formatter = "stripe_checkout_formatter"
 * )
 */
class StripeCheckoutFieldType extends DecimalItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'currency' => 'usd',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = parent::storageSettingsForm($form, $form_state, $has_data);
    $settings = $this->getSettings();

    $element['currency'] = [
      '#type' => 'textfield',
      '#title' => t('Currency'),
      '#default_value' => $settings['currency'],
      '#length' => 3,
      '#size' => 3,
      '#description' => t('The three character ISO currency code for this price.'),
      '#disabled' => $has_data,
    ];

    return $element;
  }

}
