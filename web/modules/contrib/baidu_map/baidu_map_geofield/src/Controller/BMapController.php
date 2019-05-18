<?php

namespace Drupal\baidu_map_geofield\Controller;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Baidu map controller.
 */
class BMapController extends ControllerBase {

  public function getGeocoder() {
    $config = \Drupal::config('baidu_map.settings');

    $query = array(
      'address' => '新研大厦',
      'ak' => $config->get('baidu_map_api_key'),
      'output' => 'json',
    );

    $url = Url::fromUri('http://api.map.baidu.com/geocoder/v3/', [
      'query' => $query
    ]);

  }

  /**
   * get geocoder.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array|\Symfony\Component\HttpFoundation\JsonResponse
   */
  public function getPlace(Request $request) {
    $config = \Drupal::config('baidu_map.settings');

    $query = array(
      'query' => $request->query->get('q'),
      'region' => '全国',
      'ak' => $config->get('baidu_map_api_key'),
      'output' => 'json',
      'city_limit' => 10
    );

    $url = Url::fromUri('http://api.map.baidu.com/place/v2/suggestion', [
      'query' => $query
    ]);

    $response = [];

    try {
      $request = \Drupal::httpClient()->get($url->toString(), array('headers' => array('Accept' => 'application/json')));

      $request_body = (string) $request->getBody();

      if ($request->getStatusCode() === 200 && !empty($request_body)) {
        foreach(json_decode($request_body, TRUE)['result'] as $data) {
          $response[] = [
            'label' => $data['name'],
            'value' => $data
          ];
        }
      }
    }
    catch (RequestException $e) {
      return $response;
    }

    return new JsonResponse($response);
  }
}
