<?php

namespace Drupal\shipstation_api\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Serialization\Json;

/**
 * Shipstation API Service.
 */
class ShipStationApi {
  /**
   * URL for API.
   */
  const URL = 'https://ssapi.shipstation.com/';

  /**
   * HTTP methods.
   */
  const METHOD_GET = 'GET';
  const METHOD_POST = 'POST';

  /**
   * Curl instance.
   */
  protected $curl;

  /**
   * Authorize token, for protected API methods.
   *
   * @var string
   */
  protected $token;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $shipstationConfig = $config_factory->get('shipStation_api.settings');
    $this->apiKey = $shipstationConfig->get('shipstation_api_key');
    $this->apiSecret = $shipstationConfig->get('shipstation_secret');
  }

  /**
   * Class destructor.
   */
  public function __destruct() {
    curl_close($this->curl);
  }

  /**
   * Generate url for request.
   *
   * @param string $path
   * @param string $base
   *
   * @return string
   */
  protected function getUrl($path, $base = '') {
    $base = $base ? $base : self::URL;
    return $base . $path;
  }

  /**
   * Get Orders by tag.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function ordersByTag($params = []) {
    $url = $this->getUrl('orders/listbytag');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_GET,
      $params ? http_build_query($params) : NULL,
      $headers
    );

    return $result;
  }

  /**
   * Get all possible tags.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function allTags($params = []) {
    $url = $this->getUrl('accounts/listtags');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_GET,
      $params ? http_build_query($params) : NULL,
      $headers
    );

    return $result;
  }

  /**
   * Get list of orders.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function listOrders($params = []) {
    $url = $this->getUrl('orders');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_GET,
      $params ? http_build_query($params) : NULL,
      $headers
    );

    return $result;
  }

  /**
   * Create/Update Order.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function createOrUpdateOrder($params) {
    $url = $this->getUrl('orders/createorder');
    $headers = [
      'Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret),
      'Content-Type: application/json',
    ];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Unassign user from listed orders.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function unassignUser($params) {
    $url = $this->getUrl('orders/unassignuser');
    $headers = [
      'Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret),
      'Content-Type: application/json',
    ];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Assign user from listed orders.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function assignUser($params) {
    $url = $this->getUrl('orders/assignuser');
    $headers = [
      'Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret),
      'Content-Type: application/json',
    ];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Get list of users.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function users($params = []) {
    $url = $this->getUrl('users');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_GET,
      $params ? http_build_query($params) : NULL,
      $headers
    );

    return $result;
  }

  /**
   * Register new account.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function registerAccount($params) {
    $url = $this->getUrl('accounts/registeraccount');
    $headers = [
      'Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret),
      'Content-Type: application/json',
    ];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Get all carriers.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function carriers($params = []) {
    $url = $this->getUrl('carriers');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_GET,
      $params ? http_build_query($params) : NULL,
      $headers
    );

    return $result;
  }

  /**
   * Get carrier.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function getCarrier($params = []) {
    $url = $this->getUrl('getcarrier');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_GET,
      $params ? http_build_query($params) : NULL,
      $headers
    );

    return $result;
  }

  /**
   * Adds funds to a carrier account using the payment information on file.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function addFunds($params) {
    $url = $this->getUrl('carriers/addfunds');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Retrieves a list of packages for the specified carrier.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function listPackages($params = []) {
    $url = $this->getUrl('carriers/listpackages');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_GET,
      $params ? http_build_query($params) : NULL,
      $headers
    );

    return $result;
  }

  /**
   * Retrieve list of available shipping services provided by specified carrier.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function listServices($params = []) {
    $url = $this->getUrl('carriers/listservices');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_GET,
      $params ? http_build_query($params) : NULL,
      $headers
    );

    return $result;
  }

  /**
   * Obtains a list of customers that match the specified criteria.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function listCustomers($params = []) {
    $url = $this->getUrl('customers');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_GET,
      $params ? http_build_query($params) : NULL,
      $headers
    );

    return $result;
  }

  /**
   * Get customer.
   *
   * @param int $customer_id
   *
   * @return array
   *
   * @access public
   */
  public function customer($customer_id) {
    $url = $this->getUrl('customers/' . $customer_id);
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_GET,
      [],
      $headers
    );

    return $result;
  }

  /**
   * Get product.
   *
   * @param int $product_id
   *
   * @return array
   *
   * @access public
   */
  public function getProduct($product_id) {
    $url = $this->getUrl('products/' . $product_id);
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_GET,
      [],
      $headers
    );

    return $result;
  }

  /**
   * This call does not currently support partial updates.
   *
   * The entire resource must be provided in the body of the request.
   *
   * @param array $params
   * @param int $product_id
   *
   * @return array
   *
   * @access public
   */
  public function updateProduct($params, $product_id) {
    $url = $this->getUrl('products/' . $product_id);
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * List of all products.
   *
   * @return array
   *
   * @access public
   */
  public function listProducts() {
    $url = $this->getUrl('products');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_GET,
      [],
      $headers
    );

    return $result;
  }

  /**
   * Obtains a list of products that match the specified criteria.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function listProductsWithParams($params = []) {
    $url = $this->getUrl('products');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_GET,
      $params,
      $headers
    );

    return $result;
  }

  /**
   * List of all shipments.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function listShipments($params = []) {
    $url = $this->getUrl('shipments');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_GET,
      $params,
      $headers
    );

    return $result;
  }

  /**
   * Creates a shipping label.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function createLabel($params) {
    $url = $this->getUrl('shipments/createlabel');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Retrieves shipping rates for the specified shipping details.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function retrieveRates($params) {
    $url = $this->getUrl('shipments/getrates');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Voids the specified label by shipmentId.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function voidLabel($params) {
    $url = $this->getUrl('shipments/voidlabel');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Get store.
   *
   * @param int $store_id
   *
   * @return array
   *
   * @access public
   */
  public function getStore($store_id) {
    $url = $this->getUrl('stores/' . $store_id);
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_GET,
      [],
      $headers
    );

    return $result;
  }

  /**
   * This call does not currently support partial updates.
   *
   * The entire resource must be provided in the body of the request.
   *
   * @param array $params
   * @param int $store_id
   *
   * @return array
   *
   * @access public
   */
  public function updateStore($params, $store_id) {
    $url = $this->getUrl('stores/' . $store_id);
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Initiates a store refresh.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function refreshStore($params) {
    $url = $this->getUrl('stores/refreshstore');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Retrieve the list of installed stores on the account.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function listStores($params) {
    $url = $this->getUrl('stores');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Lists the marketplaces that can be integrated with ShipStation.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function listMarketplaces($params) {
    $url = $this->getUrl('stores/marketplaces');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Deactivates the specified store.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function deactivateStore($params) {
    $url = $this->getUrl('stores/deactivate');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Reactivates the specified store.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function reactivateStore($params) {
    $url = $this->getUrl('stores/reactivate');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Get warehouses.
   *
   * @param int $warehouse_id
   *
   * @return array
   *
   * @access public
   */
  public function getWarehouses($warehouse_id) {
    $url = $this->getUrl('warehouses/' . $warehouse_id);
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_GET,
      [],
      $headers
    );

    return $result;
  }

  /**
   * This call does not currently support partial updates.
   *
   * The entire resource must be provided in the body of the request.
   *
   * @param array $params
   * @param int $warehouse_id
   *
   * @return array
   *
   * @access public
   */
  public function updateWarehouses($params, $warehouse_id) {
    $url = $this->getUrl('warehouses/' . $warehouse_id);
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Retrieves a list of your warehouses.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function listWarehouses($params) {
    $url = $this->getUrl('warehouses');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Adds a warehouse to your account.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function createWarehouse($params) {
    $url = $this->getUrl('warehouses/createwarehouse');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Subscribes to a specific type of webhook.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function subscribeWebhook($params) {
    $url = $this->getUrl('webhooks/subscribe');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Retrieves a single order from the database.
   *
   * @param array $params
   * @param int $order_id
   *
   * @return array
   *
   * @access public
   */
  public function getOrder($params, $order_id) {
    $url = $this->getUrl('orders/' . $order_id);
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_GET,
      $params ? http_build_query($params) : NULL,
      $headers
    );

    return $result;
  }

  /**
   * Adds a tag to an order.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function addOrderTag($params) {
    $url = $this->getUrl('orders/addtag');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Creates a shipping label for a given order.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function createLabelForOrder($params) {
    $url = $this->getUrl('orders/createlabelfororder');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Endpoint can be used to create or update multiple orders in one request.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function createOrUpdateOrders($params) {
    $url = $this->getUrl('orders/createorders');
    $headers = [
      'Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret),
      'Content-Type: application/json',
    ];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Method changes status of given order to On Hold until the date specified.
   *
   * When the status will automatically change to Awaiting Shipment.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function holdOrderUntil($params) {
    $url = $this->getUrl('orders/holduntil');
    $headers = [
      'Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret),
      'Content-Type: application/json',
    ];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Marks an order as shipped without creating a label in ShipStation.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function markOrderAsShipped($params) {
    $url = $this->getUrl('orders/markasshipped');
    $headers = [
      'Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret),
      'Content-Type: application/json',
    ];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Removes a tag from the specified order.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function removeTag($params) {
    $url = $this->getUrl('orders/removetag');
    $headers = [
      'Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret),
      'Content-Type: application/json',
    ];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Method changes the status of given order from On Hold to Awaiting Shipment.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function restoreFromHold($params) {
    $url = $this->getUrl('orders/restorefromhold');
    $headers = [
      'Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret),
      'Content-Type: application/json',
    ];
    $result = $this->curlRequest(
      $url,
      self::METHOD_POST,
      json_encode($params),
      $headers
    );

    return $result;
  }

  /**
   * Obtains a list of fulfillments that match the specified criteria.
   *
   * @param array $params
   *
   * @return array
   *
   * @access public
   */
  public function fulfillments($params = []) {
    $url = $this->getUrl('fulfillments');
    $headers = ['Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret)];
    $result = $this->curlRequest(
      $url,
      self::METHOD_GET,
      $params ? http_build_query($params) : NULL,
      $headers
    );

    return $result;
  }

  /**
   * Execution of the request.
   *
   * @param string $url
   * @param string $method
   * @param string $parameters
   * @param array $headers
   * @param int $timeout
   *
   * @return array
   *
   * @access protected
   *
   * @throws \Exception
   */
  protected function curlRequest($url, $method = 'GET', $parameters = '', $headers = [], $timeout = 30) {
    if ($method == self::METHOD_GET && $parameters) {
      $url .= "?$parameters";
    }

    // Get curl handler or initiate it.
    if (!$this->curl) {
      $this->curl = curl_init();
    }

    // Set general arguments.
    curl_setopt($this->curl, CURLOPT_URL, $url);
    curl_setopt($this->curl, CURLOPT_FAILONERROR, FALSE);
    curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($this->curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($this->curl, CURLOPT_HEADER, FALSE);

    // Reset some arguments, in order to avoid use some from previous request.
    curl_setopt($this->curl, CURLOPT_POST, FALSE);

    curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);

    if ($method == self::METHOD_POST && $parameters) {
      curl_setopt($this->curl, CURLOPT_POST, TRUE);

      // Encode parameters if them already not encoded in json.
      if (!is_string($parameters)) {
        $parameters = http_build_query($parameters);
      }

      curl_setopt($this->curl, CURLOPT_POSTFIELDS, $parameters);
    }

    $response = curl_exec($this->curl);
    $statusCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

    $errno = curl_errno($this->curl);
    $error = curl_error($this->curl);

    if ($errno) {
      throw new \Exception($error, $errno);
    }

    $result = Json::encode($response);

    if ($statusCode >= 400) {
      if (!empty($result['response_status']['errors'])) {
        foreach ($result['response_status']['errors'] as $error) {
          $message = '';
          if (!empty($error['error_code'])) {
            $message .= 'Error ' . $error['error_code'] . ': ';
          }
          if (!empty($error['message'])) {
            $message .= $error['message'] . ' ';
          }
          if (!empty($error['field_name'])) {
            $message .= $error['field_name'];
          }
          if (!empty($message)) {
            drupal_set_message($message, 'error');
          }
        }
      }
      throw new \Exception($result['response_status']['message'], $statusCode);
    }

    return !empty($result) ? $result : [];
  }

}
