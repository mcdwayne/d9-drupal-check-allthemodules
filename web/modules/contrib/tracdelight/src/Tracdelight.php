<?php

/**
 * @file
 * Contains Drupal\tracdelight\Tracdelight.
 */

namespace Drupal\tracdelight;

use Drupal\Component\Serialization\Json;
use \Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;

/**
 * Class Tracdelight.
 *
 * @package Drupal\tracdelight
 */
class Tracdelight implements TracdelightInterface {

  const TRACDELIGHT_API_URL = 'http://sapi.edelight.biz/api';
  const TRACDELIGHT_MAX_ITEMS = 10;

  /** @var  ClientInterface */
  protected $httpClient;

  /** @var  EntityManagerInterface */
  protected $entityManager;

  protected $accessKey;

  /**
   * @param \GuzzleHttp\ClientInterface $httpClient
   * @param $accessKey
   */
  public function __construct(ClientInterface $httpClient, EntityManagerInterface $entityManager, $accessKey) {

    $this->httpClient = $httpClient;
    $this->entityManager = $entityManager;
    $this->accessKey = $accessKey;

  }


  /**
   * @param $params
   * @param $items
   * @param $messages
   * @return \Psr\Http\Message\ResponseInterface
   */
  protected function callAPI($params, $items, &$messages) {

    if (isset($params['Query'])) {
      $params['Query'] = '"' . $params['Query'] . '"';
    }

    $url = static::TRACDELIGHT_API_URL . '?' . UrlHelper::buildQuery(
        array(
          'AccessKey' => $this->accessKey,
          'Extracted' => 'false',
          'Operation' => 'ItemSearch',
          'Items' => $items,
          'Output' => 'json',
          'Fields' => 'modified',
          'Sort' => '-modified',
        ) + $params
      );

    $messages['url'] = $url;

    return $this->httpClient->request(
      'GET',
      $url,
      array(
        'Content-Type' => 'text/xml',
      )
    );
  }

  /**
   * Retrive product from api and creates or updates the entities.
   *
   * @param $query
   * @see http://itemsearch.edelight.biz/docs/html/
   * @param int $items
   *   Number of items to be fetched
   * @return array
   *   Array of products
   * @throws \Exception
   */
  public function queryProducts($query, $items = Tracdelight::TRACDELIGHT_MAX_ITEMS) {
    $active_products = array();

    $response = $this->callAPI($query, $items, $messages);

    if ($response->getStatusCode() != 200) {
      throw new \Exception("Could not reach tracdelight API");
    }

    $response = Json::decode($response->getBody());

    if (empty($response['itemsearchresponse']['items']['item'])) {
      return $active_products;
    }

    foreach ($response['itemsearchresponse']['items']['item'] as $item) {

      $item['shop'] = $item['shop']['name'];
      $item['formattedprice'] = $item['listprice']['formattedprice'];
      $item['price'] = $item['listprice']['amount'];
      $item['oldprice'] = $item['listprice']['priceold'];
      $item['currency'] = $item['listprice']['currencycode'];
      $item['active'] = 1;
      #$item['field_product_image'][LANGUAGE_NONE][0]['fid'] = $file->fid;

      $active_products[$item['ein']] = $item;
    }

    return $active_products;
  }

  /**
   * @param $uri
   * @return bool
   */
  public function getEinFromUri($uri) {
    parse_str($uri, $query_params);

    if (is_array($query_params) && isset($query_params['ein'])) {
      return $query_params['ein'];
    }
    else {
      if (preg_match('/\,(?P<ein>[a-z0-9]{16})\,/i', $uri, $matches)) {
        return $matches['ein'];
      }
    }

    return FALSE;
  }

  /**
   * @param $string
   * @return int
   */
  public function stringSeemsToBeEin($string) {
    return preg_match('/^[a-z0-9]{16}$/i', $string);
  }


  /**
   * Retrive product from api and creates or updates the entities.
   *
   * @param $query
   * @see http://itemsearch.edelight.biz/docs/html/
   * @param int $items
   *   Number of items to be fetched
   * @param int $last_run
   *   Timestamp of the last run. Used for cron
   * @return array
   *   Array of EINs which are active
   * @throws \Exception
   */
  public function importProducts($products, $last_run = 0) {
    $active_products = array();


    foreach ($products as $item) {

      if ($last_run >= $item['modified']) {
        continue;
      }

      $image = $this->retrieveImage($item);
      if ($image->getHeader('content-type')[0] == 'image/jpeg') {
        $suffix = '.jpg';
      }
      elseif ($image->getHeader('content-type')[0] == 'image/png')  {
        $suffix = '.png';
      }
      $file = file_save_data($image->getBody(), 'public://' . $item['ein'] . $suffix, FILE_EXISTS_REPLACE);
      image_path_flush($file->getFileUri());

      $entity_id = $this->getEntityIdByEin($item['ein']);

      if ($entity_id) {

        $product = $this->entityManager
          ->getStorage('product')
          ->load($entity_id);

        $product->file->target_id = $file->id();
        $product->file->alt = $item['title'];

        foreach ($item as $key => $value) {
          if (isset($product->{$key})) {
            $product->{$key}->value = $value;
          }
        }
      }
      else {
        $item['file']['target_id'] = $file->id();
        $item['file']['title'] = $item['title'];

        $product = $this->entityManager
          ->getStorage('product')
          ->create($item);
      }
      $product->save();
      $active_products[$item['ein']] = $product;
    }
    return $active_products;
  }


  /**
   * Fetch id for an existing tracdelight entity.
   *
   * @param string $ein
   *   Edelight item number
   * @return mixed $entity_id or NULL
   *   tracdelight product entity id
   */
  public function getEntityIdByEin($ein) {

    $query = \Drupal::entityQuery('product')
      ->condition('ein', $ein);

    $result = $query->execute();
    if ($result) {
      return current(array_keys($result));
    }
    return NULL;
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
  public function retrieveImage($product, $image_path = 'src/normal/') {

    $tries = 0;
    do {

      $tries++;

      $image = $this->httpClient->request(
        'GET',
        Url::fromUri($product['imagebaseurl'] . $image_path . $product['ein'] . '.jpg', array('absolute' => TRUE))
          ->toUriString(),
        array(
          'Content-Type' => 'image/jpeg',
        )
      );

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
