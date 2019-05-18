<?php

namespace Drupal\commerce_nzpost;

use GuzzleHttp\Client;
use Drupal\commerce_shipping\Entity\ShipmentInterface;

/**
 *
 * Class RateLookupService.
 */
class RateLookupService implements RateLookupServiceInterface {

  const API_URL = 'https://api.nzpost.co.nz/ratefinder/international.json';
  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Entity\Order definition.
   *
   * @var \Drupal\commerce_order\Entity\Order
   */
  protected $order;

  /**
   * @var \Drupal\commerce_shipping\Entity\ShipmentInterface
   */
  protected $commerce_shipment;
  /**
   * Constructs a new RateLookupService object.
   */
  public function __construct(Client $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * Set the shipment for rate requests.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $commerce_shipment
   *   A Drupal Commerce shipment entity.
   */
  public function setShipment(ShipmentInterface $commerce_shipment) {
    $this->commerce_shipment = $commerce_shipment;
  }

  /**
   * @inheritdoc
   */
  public function getRates(ShipmentInterface $shipment, array $config ) {
    // All rates returned by NZ POST API ready to return from this call.
    $availableRates = [];

    if ($shipment->getShippingProfile()->get('address')->isEmpty()) {
      return [];
    }

    // There is currently no way to do a 'NOT' selection in Commerce Shipping.
    // Ideally we would only invoke this shipping method when the shipping
    // country is 'NOT NZ', but we can't do that in the UI, so we do it here
    // instead.
    $country_code = $shipment->getShippingProfile()->get('address')->first()->getCountryCode();
    // This is only for international shipments. Exit now if this is within NZ.
    if ($country_code == 'NZ') {
      return [];
    }
    
    $length = $shipment->getPackageType()->getLength()->convert('mm')->getNumber();
    $height = $shipment->getPackageType()->getHeight()->convert('mm')->getNumber();
    $thickness = $shipment->getPackageType()->getWidth()->convert('mm')->getNumber();
    $weight = $shipment->getWeight()->convert('kg')->getNumber();

    $query = [
      'api_key' => $config['api_information']['api_key'],
      'country_code' => $country_code,
      'value' => $shipment->getTotalDeclaredValue()->getNumber(),
      'length' =>  $length,
      'height' => $height,
      'thickness' => $thickness,
      'weight' => $weight,
    ];


    try {
      $request = $this->httpClient->get(SELF::API_URL, [
        'query' => $query,
      ]);

      $response = json_decode($request->getBody(), true);
    }
    catch (RequestException $e) {
      watchdog_exception('commerce_nzpost', $e);
    }

    if (isset($response['products'])) {
      $availableRates = $this->parseRates($response);
    }

    return $availableRates;
  }

  /**
   * @param $response
   *  Array of json decoded data from the NZ Post API.
   *
   * @return array
   *  Handy formatted array of available rates for a package.
   */
  private function parseRates($response) {
    $ret = [];

    if (count($response['products'])) {
      foreach ($response['products'] as $r) {
        $service_code = $r['service_code'];
        $ret[$service_code] = $r;
        $ret[$service_code]['price'] = $r['price'];
        $ret[$service_code]['price_including_gst'] = $r['price_including_gst'];
      }
    }
    return $ret;
  }

}
