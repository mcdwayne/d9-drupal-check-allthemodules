<?php

namespace Drupal\commerce_shipping_test\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\FlatRate;
use Drupal\commerce_shipping\ShippingRate;

/**
 * Provides the Dynamic shipping method. Prices multiplied by weight of package.
 *
 * @CommerceShippingMethod(
 *   id = "dynamic",
 *   label = @Translation("Dynamic by package weight"),
 * )
 */
class DynamicRate extends FlatRate {

  /**
   * {@inheritdoc}
   */
  public function calculateRates(ShipmentInterface $shipment) {
    // Rate IDs aren't used in a flat rate scenario because there's always a
    // single rate per plugin, and there's no support for purchasing rates.
    $rate_id = 0;
    $amount = $this->configuration['rate_amount'];
    $weight = $shipment->getPackageType()->getWeight()->convert('g')->getNumber() ?: 1;
    $amount = (new Price($amount['number'], $amount['currency_code']))->multiply($weight);
    $rates = [];
    $rates[] = new ShippingRate($rate_id, $this->services['default'], $amount);

    return $rates;
  }

}
