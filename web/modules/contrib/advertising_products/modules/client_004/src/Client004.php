<?php

/**
 * @file
 * Contains Drupal\client_004\Client004.
 */

namespace Drupal\client_004;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Class Client004.
 *
 * @package Drupal\client_004
 */
class Client004 {

  const CLIENT_004_MAX_ITEMS = 10;

  /** @var  ClientInterface */
  protected $httpClient;

  /** @var  string */
  protected $shopURL;

  /** @var  string */
  protected $shopName;

  /** @var  string */
  protected $imageResolution;

  /**
   * @param \GuzzleHttp\ClientInterface $httpClient
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(ClientInterface $httpClient,  ConfigFactoryInterface $configFactory) {
    $this->httpClient = $httpClient;
    $config = $configFactory->get('client_004.config');
    $this->shopURL = $config->get('shop_url');
    $this->shopName = $config->get('shop_name');
    $this->imageResolution = $config->get('image_resolution');
  }


  /**
   * @param $params
   * @param $items
   * @param $messages
   * @return \Psr\Http\Message\ResponseInterface
   * @throws GuzzleException
   */
  protected function callAPI($params, $items, &$messages) {

    if (isset($params['product_id'])) {
      $url = '/app/product_widget.do?action=ShowProduct';
      $url .= '&productId=' . $params['product_id'];
    }
    else {
      $url = '/app/product_widget.do?action=SearchProducts';
      $url .= '&query=' . $params['search'];
    }

    $url .= '&' . UrlHelper::buildQuery(
      array(
        'locale' => 'de_DE',
      )
    );

    $messages['url'] = $url;

    return $this->httpClient->request('GET', $url);
  }

  /**
   * Retrieve product from API and creates or updates the entities.
   *
   * @param $query
   * @param int $items
   *   Number of items to be fetched
   * @return array
   *   Array of products
   * @throws GuzzleException
   */
  public function queryProducts($query, $items = Client004::CLIENT_004_MAX_ITEMS) {
    $active_products = array();
    $data = $this->callAPI($query, $items, $messages);
    $result = Json::decode($data->getBody());
    \Drupal::logger('004')->notice('Result @message', array('@message' => print_r($result, TRUE)));

    if(isset($result['id'])){
      $result['detailpageurl'] = $result['url'];
      $result['shop'] = $this->shopName;
      $result['brand'] = '';
      $result['deliverytime'] = str_replace('Lieferzeit:', '', $result['availability']);
      $result['price'] = str_replace('€ ', '', $result['price']);
      $result['oldprice'] = '';
      $result['currency'] = '€';
      $result['active'] = 1;
      $active_products[$result['id']] = $result;
    }
    elseif(isset($query['product_id'])) {
      $active_products[$query['product_id']] = ['active' => FALSE];
    }
    else {
      return FALSE;
    }

    return $active_products;
  }

  /**
   * Search for products from API and create or update the entities.
   *
   * @param $query
   * @param int $items
   *   Number of items to be fetched
   * @return array
   *   Array of products
   * @throws GuzzleException
   */
  public function search($query, $items = Client004::CLIENT_004_MAX_ITEMS) {
    $active_products = array();
    $data = $this->callAPI($query, $items, $messages);
    $results = Json::decode($data->getBody());

    $results = array_shift($results);
    foreach ($results as $result) {
      $result['detailpageurl'] = $result['url'];
      $result['shop'] = $this->shopName;
      $result['brand'] = '';
      $result['deliverytime'] = str_replace('Lieferzeit:', '', $result['availability']);
      $result['price'] = str_replace('€ ', '', $result['price']);
      $result['oldprice'] = '';
      $result['currency'] = '€';
      $result['active'] = 1;
      $active_products[$result['id']] = $result;
    }

    return $active_products;
  }

  /**
   * Fetch product image from api.
   *
   * @param array $product
   *   A product array fetched from the api
   * @param string $image_path
   *   Path of the image which should be fetched. API Docs
   * @return \Psr\Http\Message\ResponseInterface
   *   Response from api
   * @throws \Exception
   */
  public function retrieveImage($product) {
    $tries = 0;
    $image_url = FALSE;
    if(!isset($product['images'])){
      return FALSE;
    }
    foreach ($product['images'][0]['sizes'] as $image_data) {
      if ($image_data['size'] >= $this->imageResolution) {
        $image_url = $image_data['url'];
        break;
      }
      $last_url = $image_data['url'];
    }
    if (!$image_url) {
      $image_url = $last_url;
    }
    $parsed_url = parse_url($image_url);
    $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
    do {
      $tries++;
      $image = $this->httpClient->request('GET', $parsed_url['path'], ['base_url' => $base_url]);

    } while (($image->getStatusCode() != 200 || !$image->getBody()) && $tries < 3);

    if (!$image->getBody()) {

      $error_msg = 'Error Message: ' . $image->getStatusCode() ? $image->getStatusCode() : "Couldn't retrieve image";

      throw new \Exception($error_msg);
    }

    if (!in_array($image->getHeader('content-type')[0], array('image/png', 'image/jpeg'))) {
      $error_msg = 'Error Message: Unexpected content type "' . $image->getHeader('content-type') . '"';
      throw new \Exception($error_msg);
    }

    return $image;
  }
}
