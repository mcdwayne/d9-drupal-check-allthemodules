<?php

namespace Drupal\commerce_canadapost\Api;

use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface;
use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_shipping\ShippingService;

use CanadaPost\Exception\ClientException;
use CanadaPost\Rating;

/**
 * Provides the default Rating API integration services.
 */
class RatingService extends RequestServiceBase implements RatingServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function getRates(ShippingMethodInterface $shipping_method, ShipmentInterface $shipment, array $options) {
    $order = $shipment->getOrder();
    $store = $order->getStore();

    // Fetch the Canada Post API settings first.
    $api_settings = $this->getApiSettings($store, $shipping_method);

    $origin_postal_code = !empty($shipping_method->getConfiguration()['shipping_information']['origin_postal_code'])
      ? $shipping_method->getConfiguration()['shipping_information']['origin_postal_code']
      : $store
        ->getAddress()
        ->getPostalCode();
    $postal_code = $shipment->getShippingProfile()
      ->get('address')
      ->first()
      ->getPostalCode();
    $weight = $shipment->getWeight()->convert('kg')->getNumber();

    try {
      // Turn on output buffering if we are in test mode.
      $test_mode = isset($api_settings['mode']) ? $api_settings['mode'] === 'test' : false;
      if ($test_mode) {
        ob_start();
      }

      $rating = $this->getRequest($api_settings);
      $response = $rating->getRates($origin_postal_code, $postal_code, $weight, $options);

      if (isset($api_settings['log']['request']) && $api_settings['log']['request']) {
        $response_output = var_export($response, TRUE);
        $message = sprintf(
          'Rating request made for order "%s". Response received: "%s".',
          $order->id(),
          $response_output
        );
        $this->logger->info($message);
      }

      $response = $this->parseResponse($response);
    }
    catch (ClientException $exception) {
      if (isset($api_settings['log']['request']) && $api_settings['log']['request']) {
        $message = sprintf(
          'An error has been returned by the Canada Post shipment method when fetching the shipping rates. The error was: "%s"',
          json_encode($exception->getResponseBody())
        );
        $this->logger->error($message);
      }

      $response = [];
    }

    // Log the output buffer if we are in test mode.
    if ($test_mode) {
      $output = ob_get_contents();
      ob_end_clean();

      if (!empty($output)) {
        $this->logger->info($output);
      }
    }

    return $response;
  }

  /**
   * Returns an initialized Canada Post Rating service.
   *
   * @param array $api_settings
   *   The Canada Post API settings.
   *
   * @return \CanadaPost\Rating
   *   The rating service class.
   */
  protected function getRequest(array $api_settings) {
    $config = $this->getRequestConfig($api_settings);

    return new Rating($config);
  }

  /**
   * Parse results from Canada Post API into ShippingRates.
   *
   * @param array $response
   *   The response from the Canada Post API Rating service.
   *
   * @return \Drupal\commerce_shipping\ShippingRate[]
   *   The Canada Post shipping rates.
   */
  private function parseResponse(array $response) {
    if (empty($response['price-quotes'])) {
      return [];
    }

    $rates = [];
    foreach ($response['price-quotes']['price-quote'] as $rate) {
      $service_code = $rate['service-code'];
      $service_name = $rate['service-name'];
      $price = new Price((string) $rate['price-details']['due'], 'CAD');

      $shipping_service = new ShippingService(
        $service_code,
        $service_name
      );
      $rates[] = new ShippingRate(
        $service_code,
        $shipping_service,
        $price
      );
    }

    return $rates;
  }

}
