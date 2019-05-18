<?php

/**
 * @file
 * Contains \Drupal\uc_canadapost\Plugin\Ubercart\ShippingQuote\CanadaPost.
 */

namespace Drupal\uc_canadapost\Plugin\Ubercart\ShippingQuote;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_quote\ShippingQuotePluginBase;

/**
 * Provides a percentage rate shipping quote plugin.
 *
 * Configures Canada Post CPC ID, available services, and other settings
 * related to shipping quotes.
 *
 * @UbercartShippingQuote(
 *   id = "canadapost",
 *   admin_label = @Translation("CanadaPost shipping quote")
 * )
 */
class CanadaPost extends ShippingQuotePluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'base_rate' => 0,
      'product_rate' => 0,
      'field' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    // Put fieldsets into vertical tabs.
    $form['canadapost-settings'] = array(
      '#type' => 'vertical_tabs',
      '#attached' => array(
        'library' => array(
          'uc_canadapost/uc_canadapost.admin.scripts',
        ),
      ),
    );

    // Container for credentials forms.
    $form['sellonline'] = array(
      '#type'          => 'fieldset',
      '#title'         => $this->t('SellOnline settings'),
      '#description'   => $this->t('Account number and authorization information.'),
      '#collapsible'   => TRUE,
      '#collapsed'     => TRUE,
      '#group'         => 'canadapost-settings',
    );

    // Form to set the merchant ID.
    $form['sellonline']['cpcid'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Canada Post merchant CPC ID'),
      '#default_value' => variable_get('cpcid', 'CPC_DEMO_XML'),
      '#required'      => TRUE,
      '#description'   => $this->t('Your Canada Post SellOnline account number. Visit http://sellonline.canadapost.ca to get one.'),
    );

    // Form to set the Canada Post server URL.
    $form['sellonline']['url'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Canada Post interface URL'),
      '#default_value' => variable_get('url', 'http://sellonline.canadapost.ca:30000/'),
      '#required'      => TRUE,
      '#description'   => $this->t('The server and port to use for shipping calculations.'),
    );

    // Form to specify ship-from postal code.
    $orig = variable_get('uc_quote_store_default_address', new stdClass());
    $form['sellonline']['postalcode'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Ship from postal code'),
      '#default_value' => variable_get('postalcode', isset($orig->postal_code) ? $orig->postal_code : ''),
      '#description'   => $this->t('Postal code to ship from. If supplied, overrides the entry in your SellOnline account.'),
    );

    // Container for service selection forms.
    $form['service_selection'] = array(
      '#type'          => 'fieldset',
      '#title'         => $this->t('Service Options'),
      '#collapsible'   => TRUE,
      '#collapsed'     => TRUE,
      '#group'         => 'canadapost-settings',
    );

    // Form to restrict Canada Post services available to customer.
    $form['service_selection']['services'] = array(
      '#type'          => 'checkboxes',
      '#title'         => $this->t('Canada Post Services'),
      '#default_value' => variable_get('services', _uc_canadapost_service_list()),
      '#options'       => _uc_canadapost_service_list(),
      '#description'   => $this->t('Select the shipping services that are available to customers. This list only serves to further restrict the services as set up in your SellOnline account. If you have not selected a service in your Canada Post account, it will not show up even if it is selected here.'),
    );

    // Container for quote options forms.
    $form['quote_options'] = array(
      '#type'          => 'fieldset',
      '#title'         => $this->t('Quote Options'),
      '#description'   => $this->t('Preferences that affect computation of quote.'),
      '#collapsible'   => TRUE,
      '#collapsed'     => TRUE,
      '#group'         => 'canadapost-settings',
    );

    // Form to specify turnaround time.
    $form['quote_options']['turnaround'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Turn-around time'),
      '#default_value' => variable_get('turnaround', '24'),
      '#description'   => $this->t('Number of hours for turn-around time before shipping. Allows rates to properly calculate extra charges for weekend delivery. Overrides the setting in your SellOnline account.'),
    );

    // Form to specify date format.
    $form['quote_options']['datefmt'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Delivery date format'),
      '#default_value' => variable_get('datefmt', ''),
      '#description'   => $this->t('Format to display estimated delivery date.'),
      '#options'       => _uc_canadapost_get_date_options(),
    );

    // Container for markup forms.
    $form['markups'] = array(
      '#type'          => 'fieldset',
      '#title'         => $this->t('Markups'),
      '#description'   => $this->t('Modifiers to the shipping weight and quoted rate'),
      '#collapsible'   => TRUE,
      '#collapsed'     => TRUE,
      '#group'         => 'canadapost-settings',
    );

    // Form to select type of rate markup.
    $form['markups']['rate_markup_type'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Rate Markup Type'),
      '#default_value' => variable_get('rate_markup_type', 'percentage'),
      '#options'       => array(
        'percentage' => $this->t('Percentage (%)'),
        'multiplier' => $this->t('Multiplier (×)'),
        'currency'   => $this->t('Addition (@currency)', ['@currency' => variable_get('uc_currency_sign', '$')]),
      ),
    );

    // Form to select type of rate amount.
    $form['markups']['rate_markup'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Canada Post Shipping Rate Markup'),
      '#default_value' => variable_get('rate_markup', '0'),
      '#description'   => $this->t('Markup shipping rate quote by dollar amount, percentage, or multiplier. Please note if this field is not blank, it overrides the "Handling fee" set up in your SellOnline account. If blank, the handling fee from your SellOnline account will be used.'),
    );

    // Form to select type of weight markup.
    $form['markups']['weight_markup_type'] = array(
      '#type'          => 'select',
      '#title'         => $this->t('Weight Markup Type'),
      '#default_value' => variable_get('weight_markup_type', 'percentage'),
      '#options'       => array(
        'percentage' => $this->t('Percentage (%)'),
        'multiplier' => $this->t('Multiplier (×)'),
        'mass'       => $this->t('Addition (@mass)', ['@mass' => '#']),
      ),
    );

    // Form to select type of weight markup amount.
    $form['markups']['weight_markup'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Canada Post Shipping Weight Markup'),
      '#default_value' => variable_get('weight_markup', '0'),
      '#description'   => $this->t('Markup Canada Post shipping weight before quote, on a per-package basis, by weight amount, percentage, or multiplier.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['base_rate'] = $form_state->getValue('base_rate');
    $this->configuration['product_rate'] = $form_state->getValue('product_rate');
    $this->configuration['field'] = $form_state->getValue('field');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuotes(OrderInterface $order) {
    $rate = $this->configuration['base_rate'];
    $field = $this->configuration['field'];

    foreach ($order->products as $product) {
      if (isset($product->nid->entity->$field->value)) {
        $product_rate = $product->nid->entity->$field->value * $product->qty->value;
      }
      else {
        $product_rate = $this->configuration['product_rate'] * $product->qty->value;
      }

      $rate += $product->price->value * floatval($product_rate) / 100;
    }


    return [$rate];
  }

}
