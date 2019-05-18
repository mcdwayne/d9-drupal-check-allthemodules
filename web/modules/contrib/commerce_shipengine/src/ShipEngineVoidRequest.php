<?php

namespace Drupal\commerce_shipengine;

use Drupal\commerce_shipping\Entity\ShipmentInterface;

/**
 * Class ShipEngineVoidRequest.
 *
 * @package Drupal\commerce_shipengine
 */
class ShipEngineVoidRequest extends ShipEngineRequest {

  /**
   * Set the shipment for void requests.
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
   * Void labels on the order.
   */
  public function voidLabel($label_id) {
    $config = $this->getConfig();
    $options = [
      'headers' => [
        'api-key' => $config['api_information']['api_key'],
        'Content-Type' => 'application/json',
      ],
    ];

    try {
      $client = \Drupal::httpClient();
      $response = $client->put("https://api.shipengine.com/v1/labels/$label_id/void", $options);
      $body = json_decode($response->getBody());
    }
    catch (\Exception $e) {
      \Drupal::logger('commerce_shipengine')->error($e->getMessage());
    }
  }
}
