<?php

namespace Drupal\commerce_shipengine;

use Drupal\commerce_shipping\Entity\ShipmentInterface;

class ShipEngineLabelRequest extends ShipEngineRequest {

  /**
   * Set the shipment for label requests.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $commerce_shipment
   *   A Drupal Commerce shipment entity.
   */
  public function setShipment(ShipmentInterface $commerce_shipment) {
    $this->commerce_shipment = $commerce_shipment;
    $config = $commerce_shipment->getShippingMethod()->getPlugin()->getConfiguration();
    $this->setConfig($config);
  }

  /**
   * Get label request for shipment.
   */
  public function getLabelRequest() {
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

    $label_request = [
      'shipment' => [
        'service_code' => $shipment->getShippingService(),
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
          'address_residential_indicator' => 'No',
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
          'address_residential_indicator' => 'No',
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
      'test_label' => true,
    ];

    return $label_request;
  }

  /**
   * Create a label for the shipment.
   */
  public function createLabel() {
    $label_request = $this->getLabelRequest();

    $config = $this->getConfig();
    $client = \Drupal::httpClient();

    $options = [
      'json' => $label_request,
      'headers' => [
        'api-key' => $config['api_information']['api_key'],
        'Content-Type' => 'application/json',
      ],
    ];

    try {
      $response = $client->post('https://api.shipengine.com/v1/labels', $options);
      if ($response) {
        $body = json_decode($response->getBody());

        $this->commerce_shipment->setTrackingCode($body->tracking_number);

        $label = [
          'url' => $body->label_download->href,
          'label_id' => $body->label_id,
          'tracking' => $body->tracking_number,
        ];

        return $label;
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('commerce_shipengine')->error($e->getMessage());
    }
  }

}