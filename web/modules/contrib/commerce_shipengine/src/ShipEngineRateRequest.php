<?php

namespace Drupal\commerce_shipengine;

use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_shipping\ShippingService;
use Drupal\commerce_store\Entity\Store;

/**
 * Class ShipEngineRateRequest.
 *
 * @package Drupal\commerce_shipengine
 */
class ShipEngineRateRequest extends ShipEngineRequest {

  /**
   * Get request json for ShipEngine rates.
   */
  public function getRateRequest() {
    // Validate a commerce shipment has been provided.
    if (empty($this->commerce_shipment)) {
      throw new \Exception('Shipment not provided');
    }

    $shipment = $this->commerce_shipment;
    $customer_address = $shipment->getShippingProfile()->get('address')->first()->getValue();

    $store = $shipment->getOrder()->getStore();
    $store_name = $store->getName();
    $store_address = $store->get('address')->first()->getValue();
    $store_phone = $store->get('field_phone')->value;

    $custom_items = [];
    foreach ($shipment->getItems() as $shipment_item) {
      $custom_items[] = [
        'description' => $shipment_item->getTitle(),
        'quantity' => (int) $shipment_item->getQuantity(),
        'value' => $shipment_item->getDeclaredValue()->getNumber(),
        'harmonized_tariff_code' => '1601.00', // Code for beef jerky. TODO: $shipment_item->getTariffCode()
        'country_of_origin' => $store_address['country_code'],
      ];
    }

    $config = $this->getConfig();
    $carriers = [
      $config['api_information']['stamps_id'],
      $config['api_information']['ups_id'],
    ];

    $rate_request = [
      'shipment' => [
        'validate_address' => 'no_validation',
        'ship_to' => [
          'name' => $customer_address['given_name'] . ' ' . $customer_address['family_name'],
          'phone' => '',
          'company_name' => $customer_address['organization'],
          'address_line1' => $customer_address['address_line1'],
          'address_line2' => $customer_address['address_line2'],
          'city_locality' => $customer_address['locality'],
          'state_province' => $customer_address['administrative_area'],
          'postal_code' => $customer_address['postal_code'],
          'country_code' => $customer_address['country_code'],
        ],
        'ship_from' => [
          'name' => $store_name,
          'phone' => $store_phone ?: '1-865-934-8000',
          'company_name' => '',
          'address_line1' => $store_address['address_line1'],
          'address_line2' => $store_address['address_line2'],
          'city_locality' => $store_address['locality'],
          'state_province' => $store_address['administrative_area'],
          'postal_code' => $store_address['postal_code'],
          'country_code' => $store_address['country_code'],
        ],
        'customs' => [
          'contents' => 'merchandise',
          'customs_items' => $custom_items,
          'non_delivery' => 'return_to_sender',
        ],
        'packages' => [
          [
            'weight' => [
              'value' => $shipment->getWeight()->getNumber(),
              'unit' => 'ounce'
            ]
          ]
        ],
      ],
      'rate_options' => [
        'carrier_ids' => $carriers,
      ]
    ];

    return $rate_request;
  }

  /**
   * Fetch rates from the ShipEngine API.
   */
  public function getRates() {
    $rate_request = $this->getRateRequest();
    $config = $this->getConfig();

    $options = [
      'json' => $rate_request,
      'headers' => [
        'api-key' => $config['api_information']['api_key'],
        'Content-Type' => 'application/json',
      ],
    ];

    $client = \Drupal::httpClient();

    try {
      $response = $client->post('https://api.shipengine.com/v1/rates', $options);
      $body = json_decode($response->getBody());

      foreach ($body->rate_response->rates as $rate) {
        if (in_array($rate->service_code, $config['services']) && (empty($rate->package_type) || $rate->package_type === 'package')) {
          $service = new ShippingService($rate->service_code, $rate->service_type);
          $amount = new Price((string) $rate->shipping_amount->amount, $rate->shipping_amount->currency);
          $rates[] = new ShippingRate($rate->rate_id, $service, $amount);
        }
      }

      return $rates;
    }
    catch (\Exception $e) {
      \Drupal::logger('commerce_shipengine')->error($e->getMessage());
    }
  }

}
