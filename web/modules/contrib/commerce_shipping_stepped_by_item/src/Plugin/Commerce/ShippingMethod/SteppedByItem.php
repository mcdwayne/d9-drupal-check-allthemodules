<?php

namespace Drupal\commerce_shipping_stepped_by_item\Plugin\Commerce\ShippingMethod;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodBase;
use Drupal\commerce_shipping\PackageTypeManagerInterface;
use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_shipping\ShippingService;

/**
 * @CommerceShippingMethod(
 *   id = "stepped_by_item",
 *   label = @Translation("Stepped rate by item quantity"),
 * )
 */
class SteppedByItem extends ShippingMethodBase {

  /**
   * Constructs a new SteppedByItem object.
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

    $this->services['default'] = new ShippingService('default', $this->configuration['rate_label']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'rate_label' => NULL,
      'rate_map' => [],
      'services' => ['default'],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['rate_label'] = [
      '#type' => 'textfield',
      '#title' => t('Rate label'),
      '#description' => t('Shown to customers during checkout.'),
      '#default_value' => $this->configuration['rate_label'],
      '#required' => TRUE,
    ];

    $form['rate_map'] = [
      '#type' => 'table',
      '#title' => t('Rate levels'),
      // TODO: figure out why this doesn't show for the table form element.
      '#description' => t("TODO"),
      '#header' => [
        t("Maximum quantity"),
        t("Price"),
      ],
    ];

    // TODO! Quick hack! This needs AJAX to add and remove items from the table
    // but it's just soooo much pain inside the plugin reference field form!
    foreach (range(0, 8) as $delta) {
      $form['rate_map'][$delta]['quantity'] = [
        '#type' => 'number',
        '#title' => t('Item quantity'),
        '#min' => 0,
        '#size' => 6,
        '#default_value' => $this->configuration['rate_map'][$delta]['quantity'] ?? '',
      ];
      $form['rate_map'][$delta]['amount'] = [
        '#type' => 'commerce_price',
        '#title' => t('Rate amount'),
      ];
      // Fiddly due to https://www.drupal.org/node/2914166.
      if (isset($this->configuration['rate_map'][$delta]['amount'])) {
        $form['rate_map'][$delta]['amount']['#default_value'] = $this->configuration['rate_map'][$delta]['amount'];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    foreach ($values['rate_map'] as $delta => $map_row_value) {
      if (($map_row_value['quantity'] === '') xor ($map_row_value['amount']['number'] === '')) {
        $form_state->setError($form['rate_map'][$delta], t('Items in a map row must either both have a value or both be empty.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);

      $this->configuration['rate_label'] = $values['rate_label'];

      $rate_map_values = [];
      foreach ($values['rate_map'] as $map_row_value) {
        // Skip anything where the quantity is an empty string.
        // Note we allow 0 as a quantity.
        if ($map_row_value['quantity'] === '') {
          continue;
        }

        $rate_map_values[] = $map_row_value;
      }

      // Sort the rows by the quantity.
      uasort($rate_map_values, function ($a, $b) {
        return SortArray::sortByKeyInt($a, $b, 'quantity');
      });

      $this->configuration['rate_map'] = $rate_map_values;
    }
  }

  /**
   * Gets a lookup array of item quantity to price from the configuration.
   *
   * @return
   *   An array whose keys are quantities and values are price arrays.
   */
  protected function getRateLookupByQuantity() {
    $lookup = [];

    foreach ($this->configuration['rate_map'] as $map_row) {
      $lookup[$map_row['quantity']] = $map_row['amount'];
    }

    // Sort the lookup. The UI should have sorted this, but make sure.
    ksort($lookup);

    return $lookup;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateRates(ShipmentInterface $shipment) {
    // Rate IDs aren't used in a flat rate scenario because there's always a
    // single rate per plugin, and there's no support for purchasing rates.
    $rate_id = 0;

    $quantity = 0;
    foreach ($shipment->getItems() as $shipment_item) {
      $quantity += $shipment_item->getQuantity();
    }

    // Calculates rates for the given shipment.
    // Find the highest rate that matches the quantity.
    foreach ($this->getRateLookupByQuantity() as $lookup_quantity => $lookup_amount) {
      // We want to stop at the first lookup quantity that is equal to or
      // greater than the shipment quantity.
      if ($lookup_quantity >= $quantity) {
        break;
      }
    }

    $amount = new Price($lookup_amount['number'], $lookup_amount['currency_code']);
    
    $rates = [];
    $rates[] = new ShippingRate($rate_id, $this->services['default'], $amount);

    return $rates;
  }

}
