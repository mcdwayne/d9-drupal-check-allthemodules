<?php

namespace Drupal\alexanders\Client;

use Drupal\alexanders\Entity\AlexandersOrder;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Drupal\Core\Url;

/**
 * Defines functions for passing data to the Alexanders API.
 *
 * @package Drupal\alexanders\Client
 */
class AlexandersApi {

  private $sandbox;
  private $baseUrl;
  private $key;
  private $config;
  private $client;

  /**
   * AlexandersApi constructor.
   *
   * @param bool $sandbox
   *   Whether to send a sandbox request, defaults to FALSE.
   */
  public function __construct() {
    $this->config = \Drupal::configFactory();
    $this->config = $this->config->getEditable('alexanders.settings');
    $this->sandbox = $this->config->get('client_enable_sandbox');
    $this->baseUrl = '';
    $this->key = $this->config->get('client_apikey');
    $this->client = \Drupal::httpClient();
  }

  /**
   * Sent a POST request to the API to create an order.
   *
   * @param \Drupal\alexanders\Entity\AlexandersOrder $order
   *   AlexandersOrder to send.
   *
   * @return bool
   *   TRUE if we created the order, FALSE otherwise.
   */
  public function createOrder(AlexandersOrder $order) {
    $orderData = $this->buildOrderData($order);
    $url = $this->generateUrl()->toString();
    try {
      $response = $this->client->post($url, $orderData);
    }
    catch (RequestException $e) {
      watchdog_exception('alexanders', $e);
      return FALSE;
    }
    return $this->processResponse($response);
  }

  /**
   * Sent a POST request to the API to create an order.
   *
   * @param \Drupal\alexanders\Entity\AlexandersOrder $order
   *   AlexandersOrder to send.
   *
   * @return bool
   *   TRUE if we created the order, FALSE otherwise.
   */
  public function updateOrder(AlexandersOrder $order) {
    $orderData = $this->buildOrderData($order, TRUE);
    $url = $this->generateUrl($order)->toString();
    try {
      $response = $this->client->put($url, $orderData);
    }
    catch (RequestException $e) {
      watchdog_exception('alexanders', $e);
      return FALSE;
    }
    return $this->processResponse($response);
  }

  /**
   * Sent a DELETE request to the API to delete an order.
   *
   * @param int $order_id
   *   ID of order to delete.
   *
   * @return bool
   *   TRUE if we deleted the order, FALSE otherwise.
   */
  public function deleteOrder($order_id) {
    $url = $this->generateUrl($order_id)->toString();
    try {
      $response = $this->client->delete($url);
    }
    catch (RequestException $e) {
      watchdog_exception('alexanders', $e);
      return FALSE;
    }
    return $this->processResponse($response);
  }

  /**
   * Aggregates order data into a format the Alexander API expects.
   *
   * @param \Drupal\alexanders\Entity\AlexandersOrder $order
   *   Order ID to build request data off of.
   * @param bool $only_shipping
   *   Whether to only return the address for order updates.
   *
   * @return array|bool
   *   Return array if successful, FALSE if order can't be loaded.
   */
  protected function buildOrderData(AlexandersOrder $order, $only_shipping = FALSE) {
    $data = [
      'headers' => ['X-API-KEY' => $this->key],
      'json' => [
        'orderKey1' => $order->id(),
        'orderKey2' => $order->label(),
        'rushOrder' => FALSE,
        'standardPrintItems' => $order->exportPrintItems(),
        'photobookItems' => $order->exportPhotobooks(),
        'inventoryItems' => $order->exportInventoryItems(),
      ],
    ];
    /** @var \Drupal\alexanders\Entity\AlexandersShipment $shipment */
    $shipment = $order->getShipment()[0];
    $data['json']['shipping'] = $shipment->export();

    if ($only_shipping) {
      return $data['json']['shipping']['address'];
    }
    return $data;
  }

  /**
   * Process responses from the Alexanders API.
   *
   * @param \GuzzleHttp\Psr7\Response $response
   *   Response object to process.
   *
   * @return bool
   *   TRUE if the request was a success, FALSE otherwise.
   */
  protected function processResponse(Response $response) {
    if ($response->getStatusCode() == 200) {
      watchdog('Alexanders API Response', $response->getBody()->getContents());
      return TRUE;
    }

    watchdog('Alexanders API Response', 'Got code other than 200, assuming NOT OKAY');
    return FALSE;
  }

  /**
   * Determine if we're using a sandbox or otherwise and give correct URL.
   *
   * @param \Drupal\alexanders\Entity\AlexandersOrder $order
   *   Order entity to build URL for.
   *
   * @return string
   *   URL to send requests to.
   */
  private function generateUrl(AlexandersOrder $order = NULL) {
    $urlString = '';
    if ($this->sandbox || !$this->key) {
      $urlString = 'https://devapi.divvy.systems/v1.0/order';
    }
    if ($order) {
      $urlString .= '/' . $order->id();
    }
    $this->baseUrl = Url::fromUri($urlString);
    return $this->baseUrl;
  }

}
