<?php

/**
 * @file
 * Contains Drupal\tracdelight_client\TracdelightClient.
 */

namespace Drupal\tracdelight_client;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\taxonomy\Entity\Term;
use GuzzleHttp\ClientInterface;

/**
 * Class TracdelightClient.
 *
 * @package Drupal\tracdelight_client
 */
class TracdelightClient {

  const TRACDELIGHT_MAX_ITEMS = 10;

  /** @var  ClientInterface */
  protected $httpClient;

  /** @var  string */
  protected $accessKey;

  /** @var  string */
  protected $imageResolution;

  /** @var  string */
  protected $vocabulary;

  /**
   * @param \GuzzleHttp\ClientInterface $httpClient
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(ClientInterface $httpClient,  ConfigFactoryInterface $configFactory) {
    $this->httpClient = $httpClient;
    $config = $configFactory->get('tracdelight_client.config');
    $this->accessKey = $config->get('access_key');
    $this->imageResolution = $config->get('image_resolution');
    $this->vocabulary = $config->get('vocabulary');
    $this->api_url = $config->get('api');
  }


  /**
   * @param $params
   * @param $items
   * @param $messages
   * @return \Psr\Http\Message\ResponseInterface
   * @throws GuzzleException
   */
  protected function callAPI($params, $items, &$messages) {

    $url = '';
    if (isset($params['EIN'])) {
      $url .= 'products/' . $params['EIN'];
    }

    $url .= '?' . UrlHelper::buildQuery(
      array(
        'accesskey' => $this->accessKey,
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
   * @see http://docs.tracdelight.com/#api-Products
   * @param int $items
   *   Number of items to be fetched
   * @return array
   *   Array of products
   * @throws GuzzleException
   */
  public function queryProducts($query, $items = TracdelightClient::TRACDELIGHT_MAX_ITEMS) {
    $active_products = array();
    $data = $this->callAPI($query, $items, $messages);
    $result = Json::decode($data->getBody());
    $result['ein'] = $result['id'];
    unset($result['id']);

    if (isset($result['category']) && $result['category']['name']) {
      $result['category_id'] = $result['category']['id'];
      $vocabulary_id = $this->vocabulary;
      if ($vocabulary_id && $result['category_id']) {
        $term = advertising_products_find_term($vocabulary_id, $result['category_id']);
        if (!$term) {
          $term = Term::create(
            [
              'vid' => $vocabulary_id,
              'name' => $result['category']['name'],
              'field_original_id' => $result['category_id'],
              'status' => 0,
            ]
          );
          $term->save();
        }
        $result['category_target_id'] = $term->id();
      }
    }

    $result['detailpageurl'] = $result['tracking'];
    $result['shop'] = $result['shop']['name'];
    $result['brand'] = $result['brand']['name'];
    $result['deliverytime'] = $result['list_price']['delivery_time'];
    $result['price'] = $result['list_price']['current'];
    $result['oldprice'] = $result['list_price']['old'];
    $result['currency'] = $result['list_price']['currency'];
    $result['formattedprice'] = number_format($result['price'], 2, ',', '') . ' €';
    $result['active'] = 1;

    $active_products[$result['ein']] = $result;

    return $active_products;
  }

  /**
   * @param $uri
   * @return bool
   */
  public function getEinFromUri($uri) {
    $parsedUrl = UrlHelper::parse($uri);
    if (isset($parsedUrl['query']['ein'])) {
      return $parsedUrl['query']['ein'];
    }
    else if (preg_match('/\,(?P<ein>[a-z0-9]{16})\,/i', $uri, $matches)) {
      return $matches['ein'];
    }

    return FALSE;
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
    if (strpos($this->api_url, '/v1/') !== FALSE) {
      $url = str_replace('/{0}/', '/' . $this->imageResolution . '/', trim($product['images']['url_template']));
    }
    else if (strpos($this->api_url, '/v2/') !== FALSE) {
      $url = str_replace('/{0}x{0}/', '/' . $this->imageResolution . 'x' . $this->imageResolution . '/', trim($product['images']['url_template']));
    }
    else {
      throw new \Exception('API URL not properly set.');
    }

    $tries = 0;
    do {

      $tries++;

      $image = $this->httpClient->request('GET', $url);

    } while (($image->getStatusCode() != 200 || !$image->getBody()) && $tries < 3);

    if (!$image->getBody()) {

      $error_msg = 'Error Message: ' . $image->getStatusCode() ? $image->getStatusCode() : "Couldn't retrieve image";

      throw new \Exception($error_msg, $product['ein'], 'original');
    }

    if (!in_array($image->getHeader('content-type')[0], array('image/png', 'image/jpeg'))) {
      $error_msg = 'Error Message: Unexpected content type "' . $image->getHeader('content-type') . '"';
      throw new \Exception($error_msg, $product['ein'], 'original');
    }

    return $image;
  }
}
