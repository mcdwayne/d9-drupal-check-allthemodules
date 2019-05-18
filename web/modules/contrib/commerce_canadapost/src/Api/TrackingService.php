<?php

namespace Drupal\commerce_canadapost\Api;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\Entity\ShippingMethodInterface;

use CanadaPost\Exception\ClientException;
use CanadaPost\Tracking;

/**
 * Provides the default Tracking API integration services.
 */
class TrackingService extends RequestServiceBase implements TrackingServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function fetchTrackingSummary($tracking_pin, ShipmentInterface $shipment) {
    // Fetch the Canada Post API settings first.
    $store = $shipment->getOrder()->getStore();
    $shipping_method = $shipment->getShippingMethod();
    if ($shipping_method instanceof ShippingMethodInterface) {
      $shipping_method = $shipping_method->getPlugin();
    }
    $api_settings = $this->getApiSettings($store, $shipping_method);

    try {
      // Turn on output buffering if we are in test mode.
      $test_mode = isset($api_settings['mode']) ? $api_settings['mode'] === 'test' : false;
      if ($test_mode) {
        ob_start();
      }

      $tracking = $this->getRequest($api_settings);
      $response = $tracking->getSummary($tracking_pin);

      if (isset($api_settings['log']['request']) && $api_settings['log']['request']) {
        $response_output = var_export($response, TRUE);
        $message = sprintf(
          'Tracking request made for tracking pin: "%s". Response received: "%s".',
          $tracking_pin,
          $response_output
        );
        $this->logger->info($message);
      }

      $response = $this->parseResponse($response);
    }
    catch (ClientException $exception) {
      if (isset($api_settings['log']['request']) && $api_settings['log']['request']) {
        $message = sprintf(
          'An error has been returned by the Canada Post shipment method when fetching the tracking summary for the tracking PIN "%s". The error was: "%s"',
          $tracking_pin,
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
   * Returns a Canada Post request service api.
   *
   * @param array $api_settings
   *   The Canada Post API settings.
   *
   * @return \CanadaPost\Tracking
   *   The Canada Post tracking request service object.
   */
  protected function getRequest(array $api_settings) {
    $config = $this->getRequestConfig($api_settings);

    return $tracking = new Tracking($config);
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
    if (!empty($response['tracking-summary']['pin-summary'])) {
      return $response['tracking-summary']['pin-summary'];
    }

    return [];
  }

}
