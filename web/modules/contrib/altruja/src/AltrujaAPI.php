<?php

namespace Drupal\altruja;

use GuzzleHttp\Exception\RequestException;

/**
 * API interaction with altruja.
 *
 * @package default
 */
class AltrujaAPI {

  public static function queryEndpoint($endpoint) {
    $client = \Drupal::httpClient();
    $api_url = 'https://www.altruja.de/api/integration/' . $endpoint;
    try {
      $request = $client->request('GET', $api_url);
      $response = json_decode($request->getBody());
      return $response;
    }
    catch (RequestException $e) {
      watchdog_exception('altruja', $e->getMessage());
    }
    catch (\Exception $e) {
      watchdog_exception('altruja', $e->getMessage());
    }
    return NULL;
  }

}