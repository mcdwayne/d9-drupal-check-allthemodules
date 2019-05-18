<?php

namespace Drupal\commerce_colissimo_shipping\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\PackageTypeManagerInterface;
use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_shipping\ShippingService;
use Drupal\physical\MeasurementType;
use Drupal\physical\Calculator;
use Drupal\physical\Weight;

/**
 * Provides the Colissimo shipping method.
 *
 * @CommerceShippingMethod(
 *   id = "colissimo_shipping",
 *   label = @Translation("Colissimo shipping"),
 * )
 */
class Colissimo extends ShippingMethodBase {

  /**
   * Constructs a new Colissimo object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_shipping\PackageTypeManagerInterface $package_type_manager
   *   The package type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PackageTypeManagerInterface $package_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $package_type_manager);

    $this->plgin_d = $plugin_definition;
    $this->services['default'] = new ShippingService('default', $this->configuration['rate_set']['rate_label']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {

    $defaul['rate_set'] = [
      'rate_label' => NULL,
      'rate_amount' => NULL,
      'min_weight' => NULL,
      'max_weight' => NULL
    ];
    $defaul['services'] = ['default'];

    return $defaul + parent::defaultConfiguration();

  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $amount = $this->configuration['rate_set']['rate_amount'];
    // A bug in the plugin_select form element causes $amount to be incomplete.
    if (isset($amount) && !isset($amount['number'], $amount['currency_code'])) {
      $amount = NULL;
    }

    $form['rate_set'] = array(
      '#type' => 'details',
      '#title' => $this->t('Rate settings'),
      '#description' => $this->t('Select your rate setting.'),
      '#open' => !$this->isConfigured(),
    );

    $form['rate_set']['rate_label'] = [
      '#type' => 'textfield',
      '#title' => t('Rate label'),
      '#description' => t('Shown to customers during checkout.'),
      '#default_value' => $this->configuration['rate_set']['rate_label'],
      '#required' => TRUE,
    ];
    $form['rate_set']['min_weight'] = [
      '#type' => 'physical_measurement',
      '#title' => $this->t('Minimun weight'),
      '#measurement_type' => 'weight',
      '#default_value' => $this->configuration['rate_set']['min_weight'],
      '#min' => 0,
      '#max' => 100,
    ];
    $form['rate_set']['max_weight'] = [
      '#type' => 'physical_measurement',
      '#title' => $this->t('Maximun weight'),
      '#measurement_type' => 'weight',
      '#default_value' => $this->configuration['rate_set']['max_weight'],
      '#required' => true,
      '#min' => 0,
      '#max' => 100,
    ];
    $form['rate_set']['rate_amount'] = [
      '#type' => 'commerce_price',
      '#title' => t('Rate amount'),
      '#default_value' => $amount,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $min = !empty($values['rate_set']['min_weight']['number']) ? $values['rate_set']['min_weight'] : ['number' => "0", 'unit' => $values['rate_set']['min_weight']['unit']];

      $max = !empty($values['rate_set']['max_weight']['number']) ? $values['rate_set']['max_weight'] : ['number' => "0", 'unit' => $values['rate_set']['max_weight']['unit']];

      $this->configuration['rate_set']['rate_label'] = $values['rate_set']['rate_label'];
      $this->configuration['rate_set']['rate_amount'] = $values['rate_set']['rate_amount'];
      $this->configuration['rate_set']['min_weight'] = $min;
      $this->configuration['rate_set']['max_weight'] = $max;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateRates(ShipmentInterface $shipment) {
    // Rate IDs aren't used in a flat rate scenario because there's always a
    // single rate per plugin, and there's no support for purchasing rates.
    $rate_id = 0;
    $min_weight = $this->configuration['rate_set']['min_weight'];
    $max_weight = $this->configuration['rate_set']['max_weight'];
    $weights = $this->getWeights($shipment, $min_weight, $max_weight);

    $quantity = 0;
    foreach ($shipment->getItems() as $shipment_item) {
      $quantity += $shipment_item->getQuantity();
    }

    $amount = $this->configuration['rate_set']['rate_amount'];
    $amount = new Price($amount['number'], $amount['currency_code']);
    $amount = $amount->multiply((string) $weights['weight_total']);

    $rates = [];
    if ($weights['weight_total'] >= $weights['min_weight_kg']->getNumber() && $weights['weight_total'] < ($weights['max_weight_kg']->getNumber())) {
      drupal_set_message(
        t(
          'I am «@label» rate, total weight: @weight_total kg, i am greater or equal tham @min_weight_kg kg but i am less than @max_weight_kg kg',
          [
            '@weight_total' => $weights['weight_total'],
            '@min_weight_kg' => !empty($weights['min_weight_kg']->getNumber()) ? $weights['min_weight_kg']->getNumber() : '0',
            '@max_weight_kg' => $weights['max_weight_kg']->getNumber(),
            '@label' => $this->configuration['rate_set']['rate_label']
          ]
        ),
        'status'
      );
      $rates[] = new ShippingRate($rate_id, $this->services['default'], $amount);
    }
    else {
      drupal_set_message(
        t(
          'I am «@label» rate, total weight: @weight_total kg, I am not beatween between @min_weight_kg kg and @max_weight_kg kg',
          [
            '@weight_total' => $weights['weight_total'],
            '@min_weight_kg' => !empty($weights['min_weight_kg']->getNumber()) ? $weights['min_weight_kg']->getNumber() : '0',
            '@max_weight_kg' => $weights['max_weight_kg']->getNumber(),
            '@label' => $this->configuration['rate_set']['rate_label']
          ]
        ),
        'warning'
      );
    }
    return $rates;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeights($shipment, $min_weight, $max_weight) {

    $min_weight = new Weight($min_weight['number'], $min_weight['unit']);
    $max_weight = new Weight($max_weight['number'], $max_weight['unit']);
    $min_weight_kg = $min_weight->convert('kg');
    $max_weight_kg = $max_weight->convert('kg');

    $weight_total = 0;
    foreach ($shipment->getItems() as $shipment_item) {
      $unit = $shipment_item->getWeight()->getUnit();
      if ($unit != 'kg') {
        $weight_kg = $shipment_item->getWeight()->convert('kg')->getNumber();
        $weight_total += $weight_kg;
      }
      else {
        $weight_kg = $shipment_item->getWeight()->getNumber();
        $weight_total += $weight_kg;
      }
    }

    return [
      'min_weight_kg' => $min_weight_kg,
      'max_weight_kg' => $max_weight_kg,
      'weight_total' => $weight_total,
    ];
  }

  /**
   * Determine if we have the minimum information.
   */
  protected function isConfigured() {
    $rate_set = $this->configuration['rate_set'];

    if (!empty($rate_set['rate_amount'])) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
