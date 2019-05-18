<?php

namespace Drupal\commerce_usps;

use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_shipping\ShippingService;

/**
 * Class USPSRateRequest.
 *
 * @package Drupal\commerce_usps
 */
class USPSRateRequestInternational extends USPSRateRequestBase implements USPSRateRequestInterface {

  /**
   * Resolve the rates from the RateRequest response.
   *
   * @param array $response
   *   The rate request array.
   *
   * @return array
   *   An array of ShippingRates or an empty array.
   */
  public function resolveRates(array $response) {
    $rates = [];
    // Parse the rate response and create shipping rates array.
    if (!empty($response['IntlRateV2Response']['Package']['Service'])) {
      foreach ($response['IntlRateV2Response']['Package']['Service'] as $service) {
        $price = $service['Postage'];
        $service_code = $service['@attributes']['ID'];
        $service_name = $this->cleanServiceName($service['SvcDescription']);

        // Only add the rate if this service is enabled.
        if (!in_array($service_code, $this->configuration['services'])) {
          continue;
        }

        $shipping_service = new ShippingService(
          $service_code,
          $service_name
        );

        $rates[] = new ShippingRate(
          $service_code,
          $shipping_service,
          new Price($price, 'USD')
        );
      }
    }

    return $rates;
  }

  /**
   * Initialize the rate request object needed for the USPS API.
   */
  public function buildRate() {
    // Invoke the parent to initialize the uspsRequest.
    parent::buildRate();

    $this->uspsRequest->setInternationalCall(TRUE);
    $this->uspsRequest->addExtraOption('Revision', 2);

    // Add each package to the request.
    // Todo: IntlRateV2 is limited to 25 packages per txn.
    foreach ($this->getPackages() as $package) {
      $this->uspsRequest->addPackage($package);
    }
  }

}
