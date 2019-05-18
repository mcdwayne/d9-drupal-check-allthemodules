<?php

namespace Drupal\commerce_tax_wa\Plugin\Commerce\TaxType;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_tax\Plugin\Commerce\TaxType\LocalTaxTypeBase;
use Drupal\commerce_tax\Plugin\Commerce\TaxType\RemoteTaxTypeInterface;
use Drupal\commerce_tax\TaxZone;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Sales Tax service for Washington State.
 *
 * @CommerceTaxType(
 *  id = "commerce_tax_wa",
 *  label = @Translation("Washington State Tax Service"),
 * )
 */
class WaTax extends LocalTaxTypeBase implements RemoteTaxTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(OrderInterface $order) {
    // Checks whether the tax type applies to the given order.
    $store = $order->getStore();
    $store_id = $store->get('store_id')->value;
    $config = $this->getConfiguration();
    $config_stores = $config['commerce_stores'];
    if (in_array($store_id, $config_stores)
    && $this->matchesAddress($store) || $this->matchesRegistrations($store)) {
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function apply(OrderInterface $order) {
    // Applies the tax type to the given order.
    $store = $order->getStore();
    $prices_include_tax = $store->get('prices_include_tax')->value;
    $matches_store_address = $this->matchesAddress($store);

    $zones = $this->getZones();
    foreach ($order->getItems() as $order_item) {
      $customer_profile = $this->resolveCustomerProfile($order_item);
      if (!$customer_profile) {
        continue;
      }
      $adjustments = $order_item->getAdjustments();
      $rates = $this->resolveRates($order_item, $customer_profile);
      // Don't overcharge a tax-exempt customer if the price is tax-inclusive.
      // A negative adjustment is added with the difference, and optionally
      // applied to the unit price in the TaxOrderProcessor.
      $negate = FALSE;
      if (!$rates && $prices_include_tax && $matches_store_address) {
        // The price difference is calculated using the store's default tax
        // type, but only if no other tax type added its own tax.
        // For example, a 12 EUR price with 20% EU VAT gets a -2 EUR
        // adjustment if the customer is from Japan, but only if no
        // Japanese tax was added due to a JP store registration.
        $positive_tax_adjustments = array_filter($adjustments, function ($adjustment) {
          /** @var \Drupal\commerce_order\Adjustment $adjustment */
          return $adjustment->getType() == 'tax' && $adjustment->isPositive();
        });
        if (empty($positive_tax_adjustments)) {
          $store_profile = $this->buildStoreProfile($store);
          $rates = $this->resolveRates($order_item, $store_profile);
          $negate = TRUE;
        }
      }
      else {
        // A different tax type added a negative adjustment, but this tax type
        // has its own tax to add, removing the need for a negative adjustment.
        $negative_tax_adjustments = array_filter($adjustments, function ($adjustment) {
          /** @var \Drupal\commerce_order\Adjustment $adjustment */
          return $adjustment->getType() == 'tax' && $adjustment->isNegative();
        });
        $adjustments = array_diff_key($adjustments, $negative_tax_adjustments);
        $order_item->setAdjustments($adjustments);
      }
      // Check for product variation type.
      $product_variation_type = $order_item->getPurchasedEntity()->bundle();
      if (in_array($product_variation_type, $this->configuration['product_variation_types'])) {
        foreach ($rates as $zone_id => $rate) {
          $zone = $zones[$zone_id];
          $unit_price = $order_item->getUnitPrice();
          $percentage = $rate->getPercentage();
          $tax_amount = $percentage->calculateTaxAmount($unit_price, $prices_include_tax);
          // Starting in Commerce 8.x-2.8, adjustment amounts no longer get automatically multiplied
          // by quantity, so we need to do it here:
          $tax_amount = $tax_amount->multiply($order_item->getQuantity());
          if ($this->shouldRound()) {
            $tax_amount = $this->rounder->round($tax_amount);
          }
          if ($prices_include_tax && !$this->isDisplayInclusive()) {
            $unit_price = $unit_price->subtract($tax_amount);
            $order_item->setUnitPrice($unit_price);
          }
          elseif (!$prices_include_tax && $this->isDisplayInclusive()) {
            $unit_price = $unit_price->add($tax_amount);
            $order_item->setUnitPrice($unit_price);
          }
          $order_item->addAdjustment(new Adjustment([
            'type' => 'tax',
            'label' => $zone->getDisplayLabel(),
            'amount' => $negate ? $tax_amount->multiply('-1') : $tax_amount,
            'percentage' => $percentage->getNumber(),
            'source_id' => $this->entityId . '|' . $zone->getId() . '|' . $rate->getId(),
            'included' => !$negate && $this->isDisplayInclusive(),
          ]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // Gets default configuration for this plugin.
    return [
      'label' => 'Seattle - 1726',
      'percentage' => '0.101',
      'product_type_variations' => [],
      'commerce_stores' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Wa Locality ID and Name'),
      '#default_value' => $this->configuration['label'],
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['percentage'] = [
      '#type' => 'commerce_number',
      '#title' => $this->t('Percentage'),
      '#default_value' => $this->configuration['percentage'] * 100,
      '#field_suffix' => $this->t('%'),
      '#min' => 0,
      '#max' => 100,
    ];
    $form['product_variation_types'] = [
      '#type' => 'commerce_entity_select',
      '#target_type' => 'commerce_product_variation_type',
      '#title' => $this->t('Product variations'),
      '#description' => $this->t('Select product variations to apply tax to.'),
      '#default_value' => $this->configuration['product_variation_types'],
      '#hide_single_entity' => FALSE,
      '#autocomplete_threshold' => 10,
      '#multiple' => TRUE,
      '#required' => TRUE,
    ];
    $form['commerce_stores'] = [
      '#type' => 'commerce_entity_select',
      '#target_type' => 'commerce_store',
      '#title' => $this->t('Commerce store'),
      '#description' => $this->t('Select store to apply tax to.'),
      '#default_value' => $this->configuration['commerce_stores'],
      '#hide_single_entity' => FALSE,
      '#autocomplete_threshold' => 10,
      '#multiple' => TRUE,
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Form submission handler.
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['label'] = $values['label'];
      $this->configuration['percentage'] = $values['percentage'] / 100;
      $this->configuration['product_variation_types'] = $values['product_variation_types'];
      $this->configuration['commerce_stores'] = $values['commerce_stores'];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getDisplayLabels() {
    return [
      'sales_tax' => $this->t('Sales Tax'),
      'food_tax' => $this->t('Food Tax'),
      'non_food_tax' => $this->t('Non-food Tax'),
      'general_tax' => $this->t('General Tax'),
      'tax' => $this->t('Tax'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildZones() {
    $zones = [];

    $zones['wa'] = new TaxZone([
      'id' => 'wa',
      'label' => $this->t('Washington State'),
      'display_label' => $this->t('WA Sales Tax'),
      'territories' => [
        ['country_code' => 'US', 'administrative_area' => 'WA'],
      ],
      'rates' => [
        [
          'id' => 'tax_wa',
          'label' => $this->configuration['label'],
          'percentages' => [
            ['number' => $this->configuration['percentage'], 'start_date' => '2008-01-01'],
          ],
          'default' => TRUE,
        ],
      ],
    ]);

    return $zones;
  }

}
